<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel;

use Gawrys\Counterparty\Ai\AiRiskStrategy;
use Gawrys\Counterparty\Ai\Prompt\RiskPromptBuilder;
use Gawrys\Counterparty\Ai\Research\AiResearchProvider;
use Gawrys\Counterparty\Ai\Tool\RegistryTool;
use Gawrys\Counterparty\Ai\Tool\ReportLookupTool;
use Gawrys\Counterparty\Ai\Tool\WebSearch\WebSearchClient;
use Gawrys\Counterparty\Ai\Tool\WebSearchTool;
use Gawrys\Counterparty\Registry\RegistryManager;
use Gawrys\Counterparty\Risk\RiskStrategy;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Builds the AI strategy ONLY when the optional gawrys/counterparty-ai package is present
 * (conditional wiring - there is no separate "-ai-laravel" package). The application must
 * bind an {@see AiResearchProvider} (the SDK adapter).
 */
final class AiStrategyFactory
{
    public static function make(Container $app, float $reviewThreshold, int $cacheTtl = 86400): RiskStrategy
    {
        if (!class_exists(AiRiskStrategy::class)) {
            throw new \RuntimeException(
                'The "ai" strategy requires the gawrys/counterparty-ai package: composer require gawrys/counterparty-ai.',
            );
        }

        $tools = [
            new RegistryTool($app->make(RegistryManager::class)),
            new ReportLookupTool(),
        ];
        if ($app->bound(WebSearchClient::class)) {
            $tools[] = new WebSearchTool($app->make(WebSearchClient::class));
        }

        return new AiRiskStrategy(
            $app->make(AiResearchProvider::class),
            new RiskPromptBuilder(),
            $tools,
            $app->make(CacheInterface::class),
            $app->make(LoggerInterface::class),
            $reviewThreshold,
            $cacheTtl,
        );
    }
}
