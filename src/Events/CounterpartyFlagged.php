<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Events;

use Gawrys\Counterparty\VerificationOutcome;

/**
 * Dispatched when a verification produces an adverse finding or requires human review.
 */
final readonly class CounterpartyFlagged
{
    public function __construct(public VerificationOutcome $outcome)
    {
    }
}
