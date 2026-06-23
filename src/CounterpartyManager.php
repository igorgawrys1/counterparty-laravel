<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Registry\RegistryDriver;
use Gawrys\Counterparty\Registry\RegistryManager;
use Gawrys\Counterparty\VerificationOutcome;
use Gawrys\Counterparty\Verifier;

/**
 * Application-facing entry point behind the {@see Facades\Counterparty} facade. Wraps the
 * core {@see Verifier} and exposes the Laravel-style `extendRegistry()` DX for registering
 * additional per-country drivers at runtime.
 */
final readonly class CounterpartyManager
{
    public function __construct(
        private Verifier $verifier,
        private RegistryManager $registries,
    ) {
    }

    public function verify(Counterparty $counterparty): VerificationOutcome
    {
        return $this->verifier->verify($counterparty);
    }

    /**
     * @param callable(): RegistryDriver $factory
     */
    public function extendRegistry(string $name, callable $factory): void
    {
        $this->registries->extend($name, $factory);
    }

    public function registries(): RegistryManager
    {
        return $this->registries;
    }
}
