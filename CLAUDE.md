# AlertStream

Laravel package for logging/alerting to Slack, Teams, Discord, Mail.
Namespace: `NightshiftFoundry\AlertStream`

## Commands

```bash
composer test        # run PHPUnit
composer lint        # check code style (php-cs-fixer)
composer lint:fix    # fix code style
composer analyse     # PHPStan static analysis
```

## Architecture

- `src/AlertChannels/` — notification delivery (Slack, Teams, Discord, Mail)
- `src/LogChannels/` — Monolog log channel drivers (separate from alert channels)
- `src/Services/AlertStreamService.php` — core dispatch logic
- `src/Providers/AlertStreamServiceProvider.php` — package bootstrap
- `config/alertstream.php` — main config
- `config/logging-alertstream.php` — log channel config

## Gotchas

- Pre-commit hook auto-installs via `composer install` (hooks path: `.githooks/`)
- `setup-test-app.sh` bootstraps a real Laravel app for integration testing
- Snapshots feature requires a DB migration (`alertstream_snapshots` table)