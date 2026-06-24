<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Rules;

use Closure;
use Gawrys\Counterparty\Check\SanctionsScreeningCheck;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Sanctions\SanctionsProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Psr\Clock\ClockInterface;

/**
 * Validates that the named entity raises no sanctions match.
 *
 * Runs ONLY sanctions screening (one provider call), not the full verification suite, so a
 * form validation does not trigger VIES/White List/registry lookups. A match is advisory
 * grounds to block onboarding pending human review - never an automated legal determination.
 */
final class NotSanctioned implements ValidationRule
{
    public function __construct(
        private readonly string $country = 'PL',
        private readonly ?SanctionsScreeningCheck $check = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\is_string($value) || $value === '') {
            $fail('The :attribute must be a name to screen.')->translate();

            return;
        }

        $check = $this->check ?? new SanctionsScreeningCheck(app(SanctionsProvider::class), app(ClockInterface::class));

        if ($check->run(new Counterparty($value, $this->country))->isAdverse()) {
            $fail('The :attribute matches a sanctions list entry and requires review.')->translate();
        }
    }
}
