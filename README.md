# Counterparty for Laravel

[![CI](https://github.com/igorgawrys1/counterparty-laravel/actions/workflows/ci.yml/badge.svg)](https://github.com/igorgawrys1/counterparty-laravel/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-8.2%20|%208.3%20|%208.4-777bb4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-11%20|%2012-ff2d20.svg)](https://laravel.com/)
[![PHPStan](https://img.shields.io/badge/PHPStan-max%20(larastan)-brightgreen.svg)](https://github.com/larastan/larastan)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

The Laravel bridge for the
[Counterparty Verification](https://github.com/igorgawrys1/counterparty-core) toolkit:
auto-wired service provider, a facade, validation rules, a queued job and an event.

> ⚠️ A **due-diligence aid**, not a guarantee of AML compliance. Risk output is advisory.

## Features

- **Zero-config auto-discovery** - PSR contracts mapped onto Laravel's HTTP client, cache
  and log; reference registries and checks registered for you.
- **Facade** - `Counterparty::verify()` and Storage-style `Counterparty::extendRegistry()`.
- **Validation rules** - `ActiveVatPayer`, `NotSanctioned`.
- **Async** - queued `VerifyCounterparty` job dispatching a `CounterpartyFlagged` event.
- **Selectable strategy & sanctions provider** via published config; conditional AI wiring.

## Installation

```bash
composer require gawrys/counterparty-laravel
php artisan vendor:publish --tag=counterparty-config
```

**Zero-config HTTP.** The provider auto-discovers an installed PSR-18 client + PSR-17
factories - on a stock Laravel app this uses the bundled **Guzzle** (Guzzle 7 is a PSR-18
client), so you don't have to install or wire anything. To use a different client, just bind
`Psr\Http\Client\ClientInterface` (and the PSR-17 factories) in your app - your binding wins.

## Usage

```php
use Gawrys\Counterparty\Laravel\Facades\Counterparty;
use Gawrys\Counterparty\Counterparty as Subject;

$outcome = Counterparty::verify(new Subject('Acme', 'PL', nip: '1234567890'));

Counterparty::extendRegistry('de', fn () => new GermanRegistryDriver(app(JsonHttpClient::class)));
```

Validation:

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
`AiResearchProvider`) to switch to advisory AI scoring - wiring is conditional on the
package being present.

Full guide: **[documentation](https://igorgawrys1.github.io/counterparty-verification/laravel/)**.

## Testing

```bash
composer check   # php-cs-fixer + PHPStan max (larastan) + PHPUnit (orchestra/testbench)
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing & Security

Pull requests welcome. Report security issues privately - see [SECURITY.md](SECURITY.md).

## Credits

- [Igor Gawrys](https://github.com/igorgawrys1)

## License

The MIT License (MIT). See [LICENSE](LICENSE).
