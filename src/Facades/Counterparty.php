<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Facades;

use Gawrys\Counterparty\Laravel\CounterpartyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Gawrys\Counterparty\VerificationOutcome verify(\Gawrys\Counterparty\Counterparty $counterparty)
 * @method static void extendRegistry(string $name, callable $factory)
 * @method static \Gawrys\Counterparty\Registry\RegistryManager registries()
 *
 * @see CounterpartyManager
 */
final class Counterparty extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CounterpartyManager::class;
    }
}
