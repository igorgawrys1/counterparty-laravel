# Changelog

All notable changes are documented here, following [Keep a Changelog](https://keepachangelog.com/)
and [Semantic Versioning](https://semver.org/).

## [0.1.1]

### Added
- Zero-config HTTP: the service provider auto-discovers an installed PSR-18 client + PSR-17
  factories (via php-http/discovery), so a stock Laravel app works out of the box using its
  bundled Guzzle. Application bindings still take precedence (`bindIf`).

### Changed
- Review fixes: validation rules run only their relevant check; unused config entries removed;
  `ai.cache_ttl` plumbed; accurate provider docblock; corrected README example.

## [0.1.0]

### Added
- `CounterpartyServiceProvider` mapping PSR contracts onto Laravel; publishable
  `config/counterparty.php` (rule_based | ai strategy; selectable sanctions provider).
- `Counterparty` facade with `verify()` and `extendRegistry()`.
- `ActiveVatPayer` / `NotSanctioned` validation rules.
- Queued `VerifyCounterparty` job + `CounterpartyFlagged` event.
- Conditional AI wiring (no `-ai-laravel` package). Analysed with larastan at PHPStan max.
