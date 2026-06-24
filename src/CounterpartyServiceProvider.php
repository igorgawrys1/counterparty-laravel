<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel;

use Gawrys\Counterparty\Adapter\Registry\CeidgRegistryDriver;
use Gawrys\Counterparty\Adapter\Registry\CrbrRegistryDriver;
use Gawrys\Counterparty\Adapter\Registry\KrsRegistryDriver;
use Gawrys\Counterparty\Adapter\Registry\RegonRegistryDriver;
use Gawrys\Counterparty\Adapter\Sanctions\OpenSanctionsProvider;
use Gawrys\Counterparty\Adapter\Sanctions\SanctionsNetworkProvider;
use Gawrys\Counterparty\Adapter\Vies\HttpViesClient;
use Gawrys\Counterparty\Adapter\Vies\ViesClient;
use Gawrys\Counterparty\Adapter\WhiteList\HttpWhiteListClient;
use Gawrys\Counterparty\Adapter\WhiteList\WhiteListClient;
use Gawrys\Counterparty\Check\Check;
use Gawrys\Counterparty\Check\RegistryCheck;
use Gawrys\Counterparty\Check\SanctionsScreeningCheck;
use Gawrys\Counterparty\Check\ViesCheck;
use Gawrys\Counterparty\Check\WhiteListCheck;
use Gawrys\Counterparty\Clock\SystemClock;
use Gawrys\Counterparty\Enum\RegistryCapability;
use Gawrys\Counterparty\Http\JsonHttpClient;
use Gawrys\Counterparty\Registry\RegistryManager;
use Gawrys\Counterparty\Report\Source;
use Gawrys\Counterparty\Risk\RiskStrategy;
use Gawrys\Counterparty\Risk\RuleBasedRiskStrategy;
use Gawrys\Counterparty\Sanctions\SanctionsManager;
use Gawrys\Counterparty\Sanctions\SanctionsProvider;
use Gawrys\Counterparty\Verifier;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Wires the toolkit into Laravel: binds the PSR-20 clock and resolves the PSR-3 logger,
 * registers the reference registries/checks, selects the risk strategy and sanctions
 * provider from config, and binds the {@see CounterpartyManager} behind the facade.
 *
 * The host application provides the PSR-18 client + PSR-17 factories (e.g. symfony/http-client
 * or a Guzzle PSR-18 adapter), and a PSR-16 cache when the AI strategy is enabled. AI wiring
 * is conditional on the optional gawrys/counterparty-ai package being installed.
 */
final class CounterpartyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/counterparty.php', 'counterparty');

        $this->app->bind(ClockInterface::class, SystemClock::class);

        $this->app->singleton(JsonHttpClient::class, static fn (Container $app): JsonHttpClient => new JsonHttpClient(
            $app->make(ClientInterface::class),
            $app->make(RequestFactoryInterface::class),
            $app->make(StreamFactoryInterface::class),
        ));

        $this->app->bind(WhiteListClient::class, static fn (Container $app): WhiteListClient => new HttpWhiteListClient($app->make(JsonHttpClient::class)));
        $this->app->bind(ViesClient::class, static fn (Container $app): ViesClient => new HttpViesClient($app->make(JsonHttpClient::class)));

        $this->app->bind(SanctionsProvider::class, fn (Container $app): SanctionsProvider => $this->buildSanctionsProvider($app));

        $this->app->singleton(RegistryManager::class, fn (Container $app): RegistryManager => $this->buildRegistryManager($app));
        $this->app->singleton(SanctionsManager::class, function (Container $app): SanctionsManager {
            $manager = new SanctionsManager();
            $manager->extend('sanctions_network', static fn (): SanctionsProvider => $app->make(SanctionsProvider::class));

            return $manager;
        });

        $this->app->singleton(RiskStrategy::class, fn (Container $app): RiskStrategy => $this->buildRiskStrategy($app));

        $this->app->singleton(Verifier::class, fn (Container $app): Verifier => new Verifier(
            $this->buildChecks($app),
            $app->make(RiskStrategy::class),
            $app->make(ClockInterface::class),
            $app->make(LoggerInterface::class),
        ));

        $this->app->singleton(CounterpartyManager::class, static fn (Container $app): CounterpartyManager => new CounterpartyManager(
            $app->make(Verifier::class),
            $app->make(RegistryManager::class),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/counterparty.php' => $this->app->configPath('counterparty.php'),
            ], 'counterparty-config');
        }
    }

    private function buildRegistryManager(Container $app): RegistryManager
    {
        $manager = new RegistryManager();
        $http = $app->make(JsonHttpClient::class);
        $registries = $this->arrayConfig('counterparty.registries');

        if ($this->registryEnabled($registries, 'krs')) {
            $whiteList = $app->make(WhiteListClient::class);
            $clock = $app->make(ClockInterface::class);
            $manager->extend('krs', static fn (): KrsRegistryDriver => new KrsRegistryDriver($http, $whiteList, $clock));
        }
        if ($this->registryEnabled($registries, 'ceidg')) {
            $token = $this->stringConfig('counterparty.registries.ceidg.token');
            $headers = $token === null ? [] : ['Authorization' => 'Bearer ' . $token];
            $manager->extend('ceidg', static fn (): CeidgRegistryDriver => new CeidgRegistryDriver($http, null, $headers));
        }
        if ($this->registryEnabled($registries, 'regon')) {
            $manager->extend('regon', static fn (): RegonRegistryDriver => new RegonRegistryDriver($http));
        }
        if ($this->registryEnabled($registries, 'crbr')) {
            $manager->extend('crbr', static fn (): CrbrRegistryDriver => new CrbrRegistryDriver($http));
        }

        return $manager;
    }

    /**
     * @return list<Check>
     */
    private function buildChecks(Container $app): array
    {
        $clock = $app->make(ClockInterface::class);

        return [
            new WhiteListCheck($app->make(WhiteListClient::class), $clock),
            new ViesCheck($app->make(ViesClient::class), $clock),
            new SanctionsScreeningCheck($app->make(SanctionsProvider::class), $clock),
            new RegistryCheck($app->make(RegistryManager::class), RegistryCapability::LegalEntityData, $clock, Source::KRS, 'PL Business Registry'),
            new RegistryCheck($app->make(RegistryManager::class), RegistryCapability::BeneficialOwners, $clock, Source::CRBR, 'PL CRBR'),
        ];
    }

    private function buildSanctionsProvider(Container $app): SanctionsProvider
    {
        $http = $app->make(JsonHttpClient::class);
        $threshold = $this->floatConfig('counterparty.sanctions.threshold', 0.7);

        if ($this->stringConfig('counterparty.sanctions.provider') === 'opensanctions') {
            return new OpenSanctionsProvider(
                $http,
                $this->stringConfig('counterparty.sanctions.opensanctions.api_key'),
                $this->stringConfig('counterparty.sanctions.opensanctions.base_uri') ?? 'https://api.opensanctions.org',
                $this->stringConfig('counterparty.sanctions.opensanctions.dataset') ?? 'sanctions',
                $threshold,
            );
        }

        return new SanctionsNetworkProvider($http);
    }

    private function buildRiskStrategy(Container $app): RiskStrategy
    {
        if ($this->stringConfig('counterparty.strategy') === 'ai') {
            return AiStrategyFactory::make(
                $app,
                $this->floatConfig('counterparty.ai.review_threshold', 0.6),
                $this->intConfig('counterparty.ai.cache_ttl', 86400),
            );
        }

        return RuleBasedRiskStrategy::withDefaultRules($this->floatConfig('counterparty.review_threshold', 0.5));
    }

    /**
     * @param array<array-key, mixed> $registries
     */
    private function registryEnabled(array $registries, string $name): bool
    {
        $config = $registries[$name] ?? null;

        return \is_array($config) && ($config['enabled'] ?? false) === true;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function arrayConfig(string $key): array
    {
        $value = $this->app->make('config')->get($key);

        return \is_array($value) ? $value : [];
    }

    private function stringConfig(string $key): ?string
    {
        $value = $this->app->make('config')->get($key);

        return \is_string($value) ? $value : null;
    }

    private function floatConfig(string $key, float $default): float
    {
        $value = $this->app->make('config')->get($key);

        return \is_int($value) || \is_float($value) ? (float) $value : $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = $this->app->make('config')->get($key);

        return \is_int($value) ? $value : $default;
    }
}
