# Changelog

All notable changes are documented here, following [Keep a Changelog](https://keepachangelog.com/)
and [Semantic Versioning](https://semver.org/).

## [0.1.0]

### Added
- `CounterpartyServiceProvider` mapping PSR contracts onto Laravel; publishable
  `config/counterparty.php` (rule_based | ai strategy; selectable sanctions provider).
- `Counterparty` facade with `verify()` and `extendRegistry()`.
- `ActiveVatPayer` / `NotSanctioned` validation rules.
- Queued `VerifyCounterparty` job + `CounterpartyFlagged` event.
- Conditional AI wiring (no `-ai-laravel` package). Analysed with larastan at PHPStan max.
