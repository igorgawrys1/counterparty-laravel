<?php

declare(strict_types=1);

namespace Gawrys\Counterparty\Laravel\Jobs;

use Gawrys\Counterparty\Counterparty;
use Gawrys\Counterparty\Laravel\CounterpartyManager;
use Gawrys\Counterparty\Laravel\Events\CounterpartyFlagged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Verifies a counterparty off the request cycle and dispatches {@see CounterpartyFlagged}
 * when the outcome warrants attention.
 */
final class VerifyCounterparty implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Counterparty $counterparty)
    {
    }

    public function handle(CounterpartyManager $manager, Dispatcher $events): void
    {
        $outcome = $manager->verify($this->counterparty);

        if ($outcome->hasAdverseFindings() || $outcome->requiresHumanReview()) {
            $events->dispatch(new CounterpartyFlagged($outcome));
        }
    }
}
