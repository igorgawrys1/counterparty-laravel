<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Rules;

use Closure;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\CheckStatus;
use Gawrys\Counterparty\Laravel\CounterpartyManager;
use Gawrys\Counterparty\Report\Source;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that a Polish NIP belongs to an active VAT payer (PL White List).
 *
 * Reminder: this is a due-diligence aid, not a guarantee of AML compliance.
 */
final class ActiveVatPayer implements ValidationRule
{
    public function __construct(private readonly ?CounterpartyManager $manager = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\is_string($value) || $value === '') {
            $fail('The :attribute must be a valid NIP.')->translate();

            return;
        }

        $manager = $this->manager ?? app(CounterpartyManager::class);
        $outcome = $manager->verify(new Counterparty($value, 'PL', nip: $value));

        foreach ($outcome->report->fromSource(Source::WHITE_LIST) as $result) {
            if ($result->status === CheckStatus::Pass) {
                return;
            }
        }

        $fail('The :attribute is not registered as an active VAT payer.')->translate();
    }
}
