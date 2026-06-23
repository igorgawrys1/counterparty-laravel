<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Rules;

use Closure;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Laravel\CounterpartyManager;
use Gawrys\Counterparty\Report\Source;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the named entity raises no sanctions match. A match is advisory grounds
 * to block onboarding pending human review — never an automated legal determination.
 */
final class NotSanctioned implements ValidationRule
{
    public function __construct(
        private readonly string $country = 'PL',
        private readonly ?CounterpartyManager $manager = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\is_string($value) || $value === '') {
            $fail('The :attribute must be a name to screen.')->translate();

            return;
        }

        $manager = $this->manager ?? app(CounterpartyManager::class);
        $outcome = $manager->verify(new Counterparty($value, $this->country));

        foreach ($outcome->report->fromSource(Source::SANCTIONS) as $result) {
            if ($result->isAdverse()) {
                $fail('The :attribute matches a sanctions list entry and requires review.')->translate();

                return;
            }
        }
    }
}
