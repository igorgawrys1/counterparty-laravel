<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Rules;

use Closure;
use Gawrys\Counterparty\Adapter\WhiteList\WhiteListClient;
use Gawrys\Counterparty\Check\WhiteListCheck;
use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Enum\CheckStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Psr\Clock\ClockInterface;

/**
 * Validates that a Polish NIP belongs to an active VAT payer (PL White List).
 *
 * Runs ONLY the White List check (one HTTP call), not the full verification suite, so a
 * form validation does not trigger VIES/sanctions/registry lookups.
 *
 * Reminder: this is a due-diligence aid, not a guarantee of AML compliance.
 */
final class ActiveVatPayer implements ValidationRule
{
    public function __construct(private readonly ?WhiteListCheck $check = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!\is_string($value) || $value === '') {
            $fail('The :attribute must be a valid NIP.')->translate();

            return;
        }

        $check = $this->check ?? new WhiteListCheck(app(WhiteListClient::class), app(ClockInterface::class));
        $counterparty = new Counterparty($value, 'PL', nip: $value);

        if ($check->supports($counterparty) && $check->run($counterparty)->status === CheckStatus::Pass) {
            return;
        }

        $fail('The :attribute is not registered as an active VAT payer.')->translate();
    }
}
