# Changelog

All notable changes to the AlertStream Laravel package are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0] - 2026-05-01

### Added
- **Webhook retry** — Slack, Teams, and Discord channels now retry failed HTTP calls once (100 ms backoff) to handle transient failures
- **Severity mapping** — new `severity_map` config key lets you override auto-detected severity per exception class
- **Context enrichers** — new `context_enrichers` config key accepts invokable classes that augment every alert's context (e.g. git SHA, tenant ID)
- **Throttling** — new `throttle.enabled` / `throttle.max_per_minute` config to rate-limit alerts per exception fingerprint and prevent storms
- **Snapshot deduplication** — identical exceptions (same class + file + line) within a configurable time window (`dedup_minutes`) increment an existing snapshot instead of creating a duplicate row; occurrences count and "last seen" timestamp displayed in views
- **Per-channel test command** — `php artisan alertstream:test slack` sends a test alert to a single channel
- **Health check endpoint** — `GET /{prefix}/health` returns a JSON summary of AlertStream's runtime config for monitoring dashboards
- **Snapshot index view** — `GET /{prefix}/snapshots` with search, severity filter, pagination, and occurrence badges
- **Laravel Notification channel** — `AlertStreamNotificationChannel` lets you compose alerts via Laravel's notification system (`toAlertStream()`)
- `ThrottleService` class
- `HealthController` and `SnapshotController` (index, show, destroy)

### Changed
- Migration adds `fingerprint`, `occurrences`, and `last_seen_at` columns to the snapshots table
- `Snapshot` model now casts `occurrences` (integer) and `last_seen_at` (datetime)
- Routes file now registers health check and snapshot index/destroy routes alongside the existing show route

## [0.1.0] - 2026-04-30

### Added
- Initial release of AlertStream package
- Service Provider for package registration
- AlertStreamService for logging and reporting alerts
- Facade for easy access to service
- TestAlertCommand for Artisan CLI
- Configuration files (main and logging)
- Exception handler (AlertStreamException)
- Environment variable support
- Full stacktrace capture for exceptions
- Multi-channel logging support
- Test suite with Orchestra Testbench
- Comprehensive documentation and usage examples

### Features
- Log alerts and debug information to multiple channels
- Capture and log full exception stacktraces
- Flexible channel configuration
- Service provider integration with Laravel
- Facade alias for convenient usage
- Artisan command for testing functionality
