<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Tests;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\CheckStatus;
use Gawrys\Counterparty\Enum\RegistryCapability;
use Gawrys\Counterparty\Laravel\CounterpartyManager;
use Gawrys\Counterparty\Laravel\CounterpartyServiceProvider;
use Gawrys\Counterparty\Laravel\Facades\Counterparty as CounterpartyFacade;
use Gawrys\Counterparty\Laravel\Tests\Fixture\FakeRegistryDriver;
use Gawrys\Counterparty\Report\Source;
use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Orchestra\Testbench\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class CounterpartyServiceProviderTest extends TestCase
{
    private Client $http;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new Psr17Factory();
        $this->http = new Client();
        $this->http->setDefaultResponse($factory->createResponse(200)->withBody($factory->createStream('{}')));

        $app = $this->app;
        self::assertNotNull($app);
        $app->instance(ClientInterface::class, $this->http);
        $app->instance(RequestFactoryInterface::class, $factory);
        $app->instance(StreamFactoryInterface::class, $factory);
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [CounterpartyServiceProvider::class];
    }

    private function queueWhiteList(string $statusVat): void
    {
        $factory = new Psr17Factory();
        $body = json_encode([
            'result' => [
                'subject' => ['name' => 'Acme', 'statusVat' => $statusVat, 'accountNumbers' => []],
                'requestId' => 'req-1',
            ],
        ], \JSON_THROW_ON_ERROR);

        $this->http->addResponse($factory->createResponse(200)->withBody($factory->createStream($body)));
    }

    public function testFacadeVerifiesActiveVatPayer(): void
    {
        $this->queueWhiteList('Czynny');

        $outcome = CounterpartyFacade::verify(new Counterparty('Acme', 'PL', nip: '1234567890'));

        $whiteList = $outcome->report->fromSource(Source::WHITE_LIST);
        self::assertNotSame([], $whiteList);
        self::assertSame(CheckStatus::Pass, $whiteList[0]->status);
    }

    public function testManagerIsResolvableAndStrategyDefaultsToRuleBased(): void
    {
        $app = $this->app;
        self::assertNotNull($app);

        self::assertTrue($app->bound(CounterpartyManager::class));
        self::assertSame('rule_based', config('counterparty.strategy'));
    }

    public function testExtendRegistryRegistersACustomDriver(): void
    {
        CounterpartyFacade::extendRegistry('de', static fn (): FakeRegistryDriver => new FakeRegistryDriver(
            [RegistryCapability::LegalEntityData],
            ['DE'],
        ));

        self::assertTrue(CounterpartyFacade::registries()->covers('DE', RegistryCapability::LegalEntityData));
    }
}
