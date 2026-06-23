# gawrys/counterparty-laravel

Laravel bridge for [`gawrys/counterparty-core`](https://github.com/igorgawrys1/counterparty-core).

> ⚠️ A **due-diligence aid**, not a guarantee of AML compliance. Risk output is advisory.

## Install

```bash
composer require gawrys/counterparty-laravel
php artisan vendor:publish --tag=counterparty-config
```

Bind a PSR-18 client and PSR-17 factories in your app (e.g. `symfony/http-client`'s
`Psr18Client`, or a Guzzle PSR-18 adapter).

## Usage

```php
use Gawrys\Counterparty\Laravel\Facades\Counterparty;

$outcome = Counterparty::verify(new \Gawrys\Counterparty\Counterparty('Acme', 'PL', nip: '1234567890'));

Counterparty::extendRegistry('de', fn ($cfg) => new GermanRegistryDriver($cfg['api_key']));
```

Validation rules:

```php
$request->validate([
    'nip'  => ['required', new \Gawrys\Counterparty\Laravel\Rules\ActiveVatPayer()],
    'name' => ['required', new \Gawrys\Counterparty\Laravel\Rules\NotSanctioned()],
]);
```

Async:

```php
\Gawrys\Counterparty\Laravel\Jobs\VerifyCounterparty::dispatch($counterparty);
// dispatches Events\CounterpartyFlagged when the outcome is adverse or needs review
```

Set `COUNTERPARTY_STRATEGY=ai` (and install `gawrys/counterparty-ai` + bind an
`AiResearchProvider`) to switch to advisory AI scoring. Wiring is conditional on the
package being present.

MIT licensed.
