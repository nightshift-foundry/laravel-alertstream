# AlertStream - Laravel Alert & Exception Reporting

[![Tests](https://github.com/nightshift-foundry/laravel-alertstream/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/nightshift-foundry/laravel-alertstream/actions/workflows/tests.yml)
[![Code Style](https://github.com/nightshift-foundry/laravel-alertstream/actions/workflows/codestyle.yml/badge.svg?branch=main)](https://github.com/nightshift-foundry/laravel-alertstream/actions/workflows/codestyle.yml)
[![codecov](https://codecov.io/gh/nightshift-foundry/laravel-alertstream/branch/main/graph/badge.svg)](https://codecov.io/gh/nightshift-foundry/laravel-alertstream)
[![Dependabot](https://img.shields.io/badge/Dependabot-enabled-brightgreen?logo=dependabot)](https://github.com/nightshift-foundry/laravel-alertstream/security)

[![Latest Stable Version](https://poser.pugx.org/nightshift-foundry/laravel-alertstream/v)](https://packagist.org/packages/nightshift-foundry/laravel-alertstream)
[![Total Downloads](https://poser.pugx.org/nightshift-foundry/laravel-alertstream/downloads)](https://packagist.org/packages/nightshift-foundry/laravel-alertstream)
[![License](https://img.shields.io/github/license/nightshift-foundry/laravel-alertstream)](LICENSE)

A lightweight, extensible Laravel package that captures exceptions and sends rich alerts to **Slack, Teams, Discord, and Mail**, plus any custom destination you build.
No configuration required for exception reporting, queue-friendly, and runs completely off your request hot path.

## Features

- 🚨 **Automatic exception reporting** - no code changes required after install
- 📡 **Built-in channels** - Slack, Microsoft Teams, Discord, Mail (one env var to activate each)
- 🔌 **Fully extensible** - implement one interface and tag it; AlertStream discovers it automatically
- ⚡  **Queue-friendly** - runs async via a queue worker by default, or inline synchronously if preferred
- 📋 **Rich context** - exception class, severity, URL, user ID, IP, user agent, environment
- 📸 **Snapshots** - persist exceptions to the database with a secure, hash-based URL for full stacktrace viewing
- 🛡️ **Throttling** - cap alerts per exception fingerprint within a configurable cooldown window
- 🔗 **Deduplication** - group identical exceptions into a single snapshot with occurrence count
- 🎯 **Severity mapping** - override auto-detected severity per exception class via config
- 🧩 **Context enrichers** - plug in custom callables to add tenant ID, git SHA, or any data to every alert
- 🎒 **Runtime context bag** - push per-request key/value data (e.g. a request ID) at runtime; captured synchronously, queue-safe, auto-cleared per request/job
- 🌐 **Extra link** - append one configurable link (dashboard, runbook, wiki page) to every alert message on every channel
- 🔔 **Notification channel** - use AlertStream as a Laravel notification channel alongside mail, SMS, etc.
- 💚 **Health check endpoint** - JSON endpoint to verify AlertStream status from monitoring dashboards
- 🔄 **Webhook retry** - automatic retry with backoff on transient webhook failures

## Installation

```bash
composer require nightshift-foundry/laravel-alertstream
```

Publish the config:

```bash
php artisan vendor:publish --tag=alertstream-config
```

## Built-in AlertChannels

Activate any channel by adding its name to `ALERTSTREAM_CHANNELS` and supplying its credentials. No code changes needed.

```env
# Comma-separated list of channels to activate
ALERTSTREAM_CHANNELS=slack,discord
```

### Slack

```env
ALERTSTREAM_CHANNELS=slack
ALERTSTREAM_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

### Microsoft Teams

Teams no longer supports direct incoming webhooks the way Slack and Discord do. The reliable approach is a **Power Automate flow** that receives the alert payload and posts it as a formatted chat message.

#### 1. Create the flow

1. Go to [make.powerautomate.com](https://make.powerautomate.com) and create a new **Instant cloud flow**
2. Choose **"When an HTTP request is received"** as the trigger
3. Add a **"Post message in a chat or channel"** action — set **Post as** to `Flow bot`, choose your team and channel
4. Set the **Message** field to the expression:
   ```
   triggerBody()?['message']
   ```
5. Save the flow — the HTTP trigger URL will appear on the trigger step. Copy it.

#### 2. Configure the package

```env
ALERTSTREAM_CHANNELS=teams
ALERTSTREAM_TEAMS_WEBHOOK=https://prod-xx.westeurope.logic.azure.com/workflows/...
```

That's it. AlertStream POSTs an HTML-formatted payload to your flow, which passes the message straight through to the channel. The message includes severity-coded colours, bold exception details, file location, environment, and a **View Full Stacktrace** link when snapshots are enabled.

> **Note:** The HTTP trigger URL contains a SAS token and acts as a secret — treat it like a password and store it in your `.env`, never in source control.

### Discord

```env
ALERTSTREAM_CHANNELS=discord
ALERTSTREAM_DISCORD_WEBHOOK=https://discord.com/api/webhooks/YOUR/WEBHOOK
```

### Mail

```env
ALERTSTREAM_CHANNELS=mail
ALERTSTREAM_MAIL_TO=alerts@your-company.com
ALERTSTREAM_MAIL_FROM=noreply@your-company.com  # optional, falls back to mail.from
```

Multiple channels at once:

```env
ALERTSTREAM_CHANNELS=slack,teams,mail
```

## Custom AlertChannels

Need PagerDuty, Telegram, OpsGenie, or your own internal system? Implement the `AlertChannel` contract and tag it. AlertStream discovers it automatically alongside the built-in ones.

### 1. Implement the contract

```php
use NightshiftFoundry\AlertStream\AlertChannels\Contracts\AlertChannel;
use Throwable;

class PagerDutyChannel implements AlertChannel
{
    public function send(string $title, Throwable $exception, array $context): void
    {
        // deliver your alert via HTTP call, SDK, or whatever you need
    }
}
```

### 2. Register and tag it in any service provider

```php
// App\Providers\AppServiceProvider (or any service provider)
public function register(): void
{
    $this->app->bind(PagerDutyChannel::class);
    $this->app->tag([PagerDutyChannel::class], 'alertstream.channel');
}
```

AlertStream iterates every class tagged `alertstream.channel`, built-in or custom, and calls `send()` on each one. No config keys to add, no arrays to update.

## Automatic Exception Reporting

Once installed, **all application exceptions are captured automatically** with no changes to `app/Exceptions/Handler.php`.

Every alert includes:

| Field | Description |
|---|---|
| Exception class & message | What went wrong |
| File & line | Where it happened |
| Severity | `critical` / `error` / `warning` (auto-detected) |
| URL, method, IP, user agent | Request context |
| User ID & email | If authenticated |
| Environment & hostname | Runtime context |

Toggle reporting:

```env
ALERTSTREAM_REPORT_EXCEPTIONS=true   # default
ALERTSTREAM_REPORT_EXCEPTIONS=false  # disable
```

## Muting Exceptions

Some exceptions are noise: 404s, validation errors, unauthenticated requests. AlertStream ships with sensible defaults already muted, and you can extend the list freely.

### Default muted exceptions

These are ignored out of the box:

| Exception | Reason |
|---|---|
| `AuthenticationException` | User not logged in, expected and not actionable |
| `AuthorizationException` | Access denied, expected and not actionable |
| `ValidationException` | Form/API validation failure, part of normal flow |
| `HttpResponseException` | Manually thrown HTTP responses, intentional |
| `NotFoundHttpException` | 404, common bot traffic and not worth alerting |
| `MethodNotAllowedHttpException` | 405, misconfigured client and not actionable |

### Adding your own muted exceptions

Append to the `mute` array in `config/alertstream.php`:

```php
'mute' => [
    // defaults are listed above, list is fully customisable
    \Illuminate\Auth\AuthenticationException::class,
    \Illuminate\Auth\AuthorizationException::class,
    \Illuminate\Validation\ValidationException::class,
    \Illuminate\Http\Exceptions\HttpResponseException::class,
    \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
    \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,

    // your own additions:
    \App\Exceptions\ExpectedBusinessException::class,
],
```

Muting is class-hierarchy aware, so muting a parent class also suppresses all its subclasses.

### Unmuting a default at runtime

If you need to re-enable reporting for a default muted class, call `report()` on the Handler in a service provider:

```php
use NightshiftFoundry\AlertStream\Exceptions\Handler as AlertStreamHandler;

public function boot(): void
{
    $this->app->make(AlertStreamHandler::class)
        ->report(\Illuminate\Auth\AuthenticationException::class);
}
```

## Queue Configuration

### Why queue matters

When an exception is thrown, AlertStream needs to send HTTP requests to Slack, Teams, Discord, etc. These calls can take 100-500 ms each. With the queue enabled, this work is handed off to a background worker instantly and the request returns to the user without waiting. With the queue disabled, all channel calls happen inline, adding their latency directly to the response time.

**Recommendation:** keep the queue enabled in production (`ALERTSTREAM_QUEUE=true`, the default). Use `false` only in local dev or when you have no queue worker.

### Queue on (default, recommended for production)

```env
ALERTSTREAM_QUEUE=true
```

When the queue is on, **you must have at least one queue worker running**, otherwise alerts will sit in the queue unprocessed. If you don't have a worker, set `ALERTSTREAM_QUEUE=false` instead.

```bash
# Minimum required: process the default queue
php artisan queue:work
```

**Preferred: give AlertStream its own named queue.** This isolates alert jobs from your business jobs and lets you tune their worker independently:

```env
ALERTSTREAM_QUEUE=true
ALERTSTREAM_QUEUE_NAME=alertstream        # recommended, dedicated queue
ALERTSTREAM_QUEUE_CONNECTION=redis        # optional, falls back to your app default
```

```bash
# Start a worker for the dedicated queue
php artisan queue:work --queue=alertstream

# Or process both your business queue and AlertStream in one worker
php artisan queue:work --queue=default,alertstream
```

If `ALERTSTREAM_QUEUE_NAME` is not set, jobs are pushed to the `default` queue and any existing worker that processes `default` will pick them up automatically with no extra configuration.

### Queue off (sync, for simple setups or local dev)

```env
ALERTSTREAM_QUEUE=false
```

All channel calls run synchronously in the same process. No worker needed. Suitable when you have no queue infrastructure, or for local development where you want to see alerts fire immediately without running a worker.

> **Note:** even with `ALERTSTREAM_QUEUE=false`, each channel still fails silently in isolation, so one broken webhook will never crash the application.

## Snapshots (opt-in)

Exception messages in Slack, Teams, Discord, or email are often truncated or hard to read. Snapshots solve this by **persisting every exception to your database** and including a secure, hash-based URL in every channel message. Click through to see the full stacktrace, context, and metadata in a clean web view.

### Enabling snapshots

```env
ALERTSTREAM_SNAPSHOTS=true
```

When enabled, AlertStream will:
- **Load its migration** - creates the `alertstream_snapshots` table
- **Register routes** - a `GET /alertstream/snapshots/{hash}` endpoint to view snapshots
- **Register the prune command** - `alertstream:prune-snapshots`
- **Include a "View Full Stacktrace" link** in every channel message automatically

When disabled (the default), none of the above happens. No migrations are loaded, nothing is written to the database, no routes exist.

### Running the migration

```bash
php artisan migrate
```

### Configuration

```env
ALERTSTREAM_SNAPSHOTS=true
ALERTSTREAM_SNAPSHOTS_RETENTION=30                    # days before pruning
ALERTSTREAM_SNAPSHOTS_ROUTE_PREFIX=alertstream         # URL prefix
```

In `config/alertstream.php` you can also set `route_middleware` to protect the snapshots viewer (default: `['web']`):

```php
'snapshots' => [
    'enabled' => env('ALERTSTREAM_SNAPSHOTS', false),
    'table' => env('ALERTSTREAM_SNAPSHOTS_TABLE', 'alertstream_snapshots'),
    'retention_days' => env('ALERTSTREAM_SNAPSHOTS_RETENTION', 30),
    'route_prefix' => env('ALERTSTREAM_SNAPSHOTS_ROUTE_PREFIX', 'alertstream'),
    'route_middleware' => ['web'],          // add 'auth' if you want login-protected access
],
```

### Security

Snapshot URLs use a **64-character SHA-256 hash**, making them unguessable and non-sequential. If you need additional protection, add `'auth'` or a custom middleware to `route_middleware`.

### Pruning old snapshots

Snapshots are automatically eligible for pruning after 30 days (configurable via `ALERTSTREAM_SNAPSHOTS_RETENTION`), but **you must schedule the pruning command yourself** since Laravel packages cannot register scheduled tasks on behalf of the host application.

> ⚠️ **Required:** add one of the following to your scheduler, otherwise old snapshots will accumulate indefinitely.

**Option 1 - Laravel's built-in model pruning** (recommended if you already use it):

```php
// app/Console/Kernel.php or routes/console.php (Laravel 11+)
$schedule->command('model:prune')->daily();
```

The `Snapshot` model uses `MassPrunable`, so Laravel discovers and prunes it automatically alongside any other prunable models in your app.

**Option 2 - Dedicated AlertStream command:**

```php
// app/Console/Kernel.php or routes/console.php (Laravel 11+)
$schedule->command('alertstream:prune-snapshots')->daily();
```

Or run it manually:

```bash
php artisan alertstream:prune-snapshots           # uses configured retention (default: 30 days)
php artisan alertstream:prune-snapshots --days=7   # override
```

### Deleting individual snapshots

Each snapshot page includes a **Delete** button. Clicking it removes the snapshot immediately, which is useful when a snapshot contains sensitive information and you don't want to wait for automatic pruning.

### Customising the snapshot view

```bash
php artisan vendor:publish --tag=alertstream-views
```

This publishes the view to `resources/views/vendor/alertstream/snapshots/show.blade.php` where you can customise the layout, styling, and content.

## Extra Link

Append one extra link to every alert message on every alert channel (Slack, Teams, Discord, Mail) — a dashboard, runbook, on-call wiki page, whatever your team wants one click away. It's rendered right next to the snapshot link.

```env
ALERTSTREAM_EXTRA_LINK_URL=https://wiki.your-company.com/runbooks/payments
ALERTSTREAM_EXTRA_LINK_TEXT="Open Runbook"   # optional, defaults to "More information"
```

Leave `ALERTSTREAM_EXTRA_LINK_URL` unset to omit the link entirely. It's applied to the built-in channels only — custom channels don't get it automatically, but can opt in by reading `alertstream.extra_link` from config themselves.

## Manual Usage

AlertStream provides two distinct ways to send messages:

AlertStream keeps these two pipelines fully separate so an exception is never delivered twice. `report()` talks to the **alert** channels; `log()` talks to the **log** channels. Both also write to the always-on `alertstream` file (`storage/logs/alertstream.log`).

| | `report()` | `log()` |
|---|---|--|
| **Purpose** | Exception alerts that need human attention | Structured diagnostic / operational log messages |
| **Writes to the `alertstream` file** | ✅ | ✅ |
| **Dispatches to ALERT channels** (`ALERTSTREAM_CHANNELS`) | ✅ | ✗ |
| **Dispatches to LOG channels** (`ALERTSTREAM_LOG_CHANNELS`) | ✗ | ✅ |
| **Creates snapshots** | ✅ (when enabled) | ✗ |
| **Subject to throttling / dedup** | ✅ | ✗ |
| **Accepts a Throwable** | ✅ (second argument) | ✗ |

### `report()` - exception alerts

Use `report()` when something goes wrong and someone should know about it. The message is written to the `alertstream` file **and** dispatched to every active alert channel (`ALERTSTREAM_CHANNELS`: Slack, Teams, Discord, Mail, or any custom channel). It does **not** touch the log webhook channels. Snapshots, throttling, and deduplication all apply.

```php
use NightshiftFoundry\AlertStream\Facades\AlertStream;

AlertStream::report('Payment gateway timeout', $exception, ['order_id' => 42]);
```

### `log()` - structured logging at any level

For operational visibility, diagnostics, and auditing, use `log()`. It writes to the `alertstream` file **and** delivers to the log webhook channels you select via `ALERTSTREAM_LOG_CHANNELS`. It does **not** dispatch to the alert channels, create snapshots, or go through throttling.

```php
AlertStream::log(string|AlertStreamLogLevel $level, string $message, mixed $data = null, array $context = []);
```

The `$level` parameter accepts the `AlertStreamLogLevel` enum (recommended) or any equivalent log level string that Laravel supports (the standard PSR-3 levels):

| `AlertStreamLogLevel` case | String | Typical use |
|---|---|---|
| `AlertStreamLogLevel::EMERGENCY` | `emergency` | System is unusable |
| `AlertStreamLogLevel::ALERT` | `alert` | Action must be taken immediately |
| `AlertStreamLogLevel::CRITICAL` | `critical` | Critical conditions |
| `AlertStreamLogLevel::ERROR` | `error` | Runtime errors that don't require immediate action |
| `AlertStreamLogLevel::WARNING` | `warning` | Exceptional occurrences that are not errors |
| `AlertStreamLogLevel::NOTICE` | `notice` | Normal but significant events |
| `AlertStreamLogLevel::INFO` | `info` | Interesting events (user login, scheduled job ran) |
| `AlertStreamLogLevel::DEBUG` | `debug` | Detailed debug information |

Examples:

```php
use NightshiftFoundry\AlertStream\Enums\AlertStreamLogLevel;
use NightshiftFoundry\AlertStream\Facades\AlertStream;

AlertStream::log(AlertStreamLogLevel::DEBUG, 'Slow query detected', ['sql' => $query, 'time_ms' => 320]);
AlertStream::log(AlertStreamLogLevel::INFO, 'User exported report', ['user_id' => $user->id, 'rows' => 1_200]);
AlertStream::log(AlertStreamLogLevel::WARNING, 'Disk usage above 80%', ['disk' => '/dev/sda1', 'usage' => '82%']);
AlertStream::log(AlertStreamLogLevel::ERROR, 'Redis connection lost, falling back to file cache');
AlertStream::log(AlertStreamLogLevel::CRITICAL, 'Queue worker stalled', ['queue' => 'payments', 'pending' => 847]);
AlertStream::log(AlertStreamLogLevel::EMERGENCY, 'All database connections exhausted');

// Plain PSR-3 strings are still accepted for backward compatibility:
AlertStream::log('info', 'User exported report', ['user_id' => $user->id]);
```

A `debug()` convenience method is also available as a shorthand:

```php
// These two calls are identical:
AlertStream::debug('Cache miss', ['key' => 'user:42']);
AlertStream::log('debug', 'Cache miss', ['key' => 'user:42']);
```

> **When should I use `report()` vs `log()`?**
> Use `report()` when you have a caught exception and want the team notified via the alert channels (`ALERTSTREAM_CHANNELS`). Use `log()` for structured, levelled operational messages — it delivers to its own, separate set of log channels (`ALERTSTREAM_LOG_CHANNELS`).

### Symmetric alert vs log delivery

`report()` and `log()` use two parallel, independent pipelines. The same exception is therefore never delivered through two different webhooks.

| | Exceptions — `report()` | Logs — `log()` |
|---|---|---|
| Active list env | `ALERTSTREAM_CHANNELS` | `ALERTSTREAM_LOG_CHANNELS` |
| Per-channel webhook env | `ALERTSTREAM_<NAME>_WEBHOOK` | `ALERTSTREAM_LOG_<NAME>_WEBHOOK` |
| Channel classes | `src/AlertChannels/` | `src/LogChannels/` |
| Local file channel | `alertstream` (`storage/logs/alertstream.log`) | `alertstream_log` (`storage/logs/alertstream-log.log`) |

Both lists accept the same vocabulary of channel names: `slack`, `teams`, `discord`, `mail`.

```dotenv
# Exceptions go to this Teams channel:
ALERTSTREAM_CHANNELS=teams
ALERTSTREAM_TEAMS_WEBHOOK=https://outlook.office.com/webhook/AAA

# Logs go to a *different* Teams channel:
ALERTSTREAM_LOG_CHANNELS=teams
ALERTSTREAM_LOG_TEAMS_WEBHOOK=https://outlook.office.com/webhook/BBB
```

Two `daily` file channels are auto-registered so you keep a local record even with no webhooks configured — and, importantly, so the file trail mirrors the same separation as the webhooks: `alertstream` (→ `storage/logs/alertstream.log`) is written to **only** by `report()`, and `alertstream_log` (→ `storage/logs/alertstream-log.log`) is written to **only** by `log()`. Neither method ever writes to the other's file. All auto-registered channels are only defined when your app hasn't already declared a channel of the same name, so they remain fully overridable in `config/logging.php`.

**No shared destinations:** each `ALERTSTREAM_LOG_<NAME>_WEBHOOK` must be set explicitly. There is no fallback to `ALERTSTREAM_<NAME>_WEBHOOK` — if a log destination is unset, `log()` silently skips that channel rather than reusing the alert webhook. This keeps the two streams fully independent: exception alerts from `report()` never show up where you're only expecting `log()` output.

### Dependency injection

```php
use NightshiftFoundry\AlertStream\Services\AlertStreamService;

class OrderService
{
    public function __construct(private AlertStreamService $alertStream) {}

    public function charge(): void
    {
        try {
            // ...
        } catch (Throwable $e) {
            $this->alertStream->report('Charge failed', $e, ['order_id' => $this->id]);
        }
    }
}
```

### Artisan test command

```bash
php artisan alertstream:test                  # test all active alert channels (sends a report)
php artisan alertstream:test slack            # test only the Slack ALERT channel (report())
php artisan alertstream:test discord          # test only the Discord ALERT channel (report())

php artisan alertstream:test --type=debug     # test the debug log path, all configured log channels
php artisan alertstream:test --type=info      # test the info log path, all configured log channels
php artisan alertstream:test --type=warning   # test the warning log path, all configured log channels
php artisan alertstream:test --type=error     # test the error log path, all configured log channels

php artisan alertstream:test teams --type=info    # test ONLY the Teams LOG channel (log()), isolated from the Teams alert channel
```

The `--type` flag accepts any log level string and defaults to `alert` (which triggers `report()`). Any other value is passed directly to `log()` at that level.

Pairing `channel` with `--type` targets exactly one destination in exactly one pipeline — `alertstream:test teams` only ever touches the Teams entry under `ALERTSTREAM_CHANNELS`/`ALERTSTREAM_TEAMS_WEBHOOK`, while `alertstream:test teams --type=info` only ever touches the Teams entry under `ALERTSTREAM_LOG_CHANNELS`/`ALERTSTREAM_LOG_TEAMS_WEBHOOK`. The command's status report at the end always lists both pipelines separately, so you can confirm each destination is configured (and that neither is silently reusing the other's webhook) in one run.

## Throttling

Cap alerts for the same exception (same class + file + line) — whether it fires a hundred times in a burst, or recurs every few minutes forever. The first occurrence opens a fixed window lasting `ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES` and always alerts. Up to `ALERTSTREAM_THROTTLE_MAX` occurrences total are allowed inside that same window; once the cap is hit, every further occurrence is silently dropped until the window elapses, then the next occurrence alerts again and opens a fresh window.

```env
ALERTSTREAM_THROTTLE=true
ALERTSTREAM_THROTTLE_MAX=5                    # at most 5 alerts per exception fingerprint...
ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES=60      # ...per 60-minute window
```

The window is fixed — it's set once, on the first occurrence, and never extended by later ones — so a bug recurring every few minutes forever is still capped at `ALERTSTREAM_THROTTLE_MAX` alerts per window, not delivered on every single occurrence. Snapshots (if enabled) still record every occurrence via `dedup_minutes`, but only the channel notifications are throttled.

## Severity Mapping

AlertStream auto-detects severity based on exception type (`PDOException` -> critical, `HttpResponseException` -> warning, etc.). Override or extend this via `severity_map` in config:

```php
// config/alertstream.php
'severity_map' => [
    \App\Exceptions\PaymentFailedException::class => 'critical',
    \App\Exceptions\RateLimitException::class     => 'warning',
],
```

The map is checked via `instanceof`, so parent classes cover their subclasses.

## Context Enrichers

Add custom data to every alert without modifying AlertStream internals. Each enricher is an invokable class:

```php
// app/AlertStream/AddGitSha.php
class AddGitSha
{
    public function __invoke(array $context, \Throwable $e): array
    {
        $context['git_sha'] = config('app.git_sha');
        return $context;
    }
}
```

Register enrichers in config:

```php
// config/alertstream.php
'context_enrichers' => [
    \App\AlertStream\AddGitSha::class,
    \App\AlertStream\AddTenantId::class,
],
```

Enrichers run in order. If one throws, it is silently skipped and reporting continues with the remaining enrichers.

## Runtime Context

Push key/value data onto AlertStream at runtime — from a middleware, a controller, anywhere — and it gets merged into every exception context reported for the rest of the current request, console command, or queue job:

```php
use NightshiftFoundry\AlertStream\Facades\AlertStream;

AlertStream::addContext([
    'request_id' => $requestId,
    'tenant_id'  => $tenant->id,
    'git_sha'    => fn (\Throwable $e) => config('app.git_sha'),
]);
```

A value may be a plain scalar/array, or a closure. Closures are resolved lazily at report time, receiving the exception being reported as their argument — handy for values that are only worth computing when something actually goes wrong. If a closure throws, only its own key is dropped; reporting continues unaffected.

Runtime context is captured **synchronously**, inside `Handler::buildContext()`, before the exception is ever handed to the queue — so it works safely even with `queue` enabled (the default). Reading it any later, e.g. from the queued listener, would be too late: the bag is emptied by then.

The bag lives on the underlying service singleton, so it is automatically cleared at the end of every request/console lifecycle and after every processed queue job — nothing carries over from one request or job to the next, even under Octane or a long-running queue worker.

```env
ALERTSTREAM_RUNTIME_CONTEXT=true   # default; set to false to disable the bag entirely
```

## Snapshot Deduplication

When a snapshot already exists for the same exception (same class + file + line) within the configured window, AlertStream increments the existing snapshot's occurrence counter instead of creating a duplicate row.

```env
ALERTSTREAM_SNAPSHOTS_DEDUP_MINUTES=60   # default, groups identical exceptions within 1 hour
```

Set to `0` to disable deduplication and create a new row for every exception.

The snapshot index view displays the occurrence count as a badge (e.g. "12x") and the detail view shows "Last seen" alongside the original timestamp.

## Notification Channel

Use AlertStream as a native Laravel notification channel and compose alerts with the same API you already use for mail, SMS, database, etc.

```php
use Illuminate\Notifications\Notification;
use NightshiftFoundry\AlertStream\AlertChannels\AlertStreamNotificationChannel;

class PaymentFailed extends Notification
{
    public function via($notifiable): array
    {
        return [AlertStreamNotificationChannel::class, 'mail'];
    }

    public function toAlertStream($notifiable): array
    {
        return [
            'message'   => 'Payment failed for order #' . $this->order->id,
            'exception' => $this->exception,   // optional
            'context'   => ['amount' => $this->order->total],
        ];
    }
}
```

## Health Check Endpoint

A JSON endpoint is registered at `GET /{route_prefix}/health` to check AlertStream's runtime configuration. Useful for uptime monitors and deployment verification.

```bash
curl https://your-app.com/alertstream/health
```

Response:

```json
{
    "status": "active",
    "channels": ["slack", "discord"],
    "queue": { "enabled": true, "connection": "redis", "name": "alertstream" },
    "snapshots": { "enabled": true, "table": "alertstream_snapshots" },
    "throttle": { "enabled": true, "max": 5, "cooldown_minutes": 60 },
    "report_exceptions": true,
    "muted_count": 6
}
```

## Configuration Reference

### Core

| Key | Env | Default | Description |
|---|---|---|---|
| `enabled` | `ALERTSTREAM_ENABLED` | `true` | Master on/off switch |
| `report_exceptions` | `ALERTSTREAM_REPORT_EXCEPTIONS` | `true` | Auto-capture exceptions |
| `level` | `ALERTSTREAM_LEVEL` | `alert` | Log level for Laravel log channels |
| `log_channels` | `ALERTSTREAM_LOG_CHANNELS` | `single` | Laravel logging channels (comma-separated) |
| `include_stacktrace` | `ALERTSTREAM_INCLUDE_STACKTRACE` | `true` | Attach full stack trace |

### Queue

| Key | Env | Default | Description |
|---|---|---|---|
| `queue` | `ALERTSTREAM_QUEUE` | `true` | Hand off to a queue worker (faster) |
| `queue_connection` | `ALERTSTREAM_QUEUE_CONNECTION` | _(app default)_ | Queue connection |
| `queue_name` | `ALERTSTREAM_QUEUE_NAME` | `default` | Queue name |

### AlertChannels

| Key | Env | Default | Description |
|---|---|---|---|
| `channels.active` | `ALERTSTREAM_CHANNELS` | _(none)_ | Comma-separated list of active channels |
| `channels.slack.webhook` | `ALERTSTREAM_SLACK_WEBHOOK` | - | Slack incoming webhook URL |
| `channels.teams.webhook` | `ALERTSTREAM_TEAMS_WEBHOOK` | - | Teams incoming webhook URL |
| `channels.discord.webhook` | `ALERTSTREAM_DISCORD_WEBHOOK` | - | Discord webhook URL |
| `channels.mail.to` | `ALERTSTREAM_MAIL_TO` | - | Alert recipient address |
| `channels.mail.from` | `ALERTSTREAM_MAIL_FROM` | _(mail.from)_ | Sender address |

### Extra Link

| Key | Env | Default | Description |
|---|---|---|---|
| `extra_link.url` | `ALERTSTREAM_EXTRA_LINK_URL` | _(none)_ | Extra link URL appended to every alert message on all alert channels |
| `extra_link.text` | `ALERTSTREAM_EXTRA_LINK_TEXT` | `More information` | Display text for the extra link |

### Throttling

| Key | Env | Default | Description |
|---|---|---|---|
| `throttle.enabled` | `ALERTSTREAM_THROTTLE` | `true` | Enable per-exception throttling |
| `throttle.max` | `ALERTSTREAM_THROTTLE_MAX` | `5` | Max alerts per fingerprint allowed within the cooldown window |
| `throttle.cooldown_minutes` | `ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES` | `60` | Fixed window length (minutes) the max applies to |

### Severity & Enrichment

| Key | Type | Description |
|---|---|---|
| `severity_map` | `array` | `ExceptionClass::class => 'critical'\|'error'\|'warning'` |
| `context_enrichers` | `array` | Invokable class FQCNs that augment every alert context |
| `runtime_context` | `bool` | Enable the `AlertStream::addContext()` runtime context bag (`ALERTSTREAM_RUNTIME_CONTEXT`, default `true`) |

### Snapshots

| Key | Env | Default | Description |
|---|---|---|---|
| `snapshots.enabled` | `ALERTSTREAM_SNAPSHOTS` | `false` | Enable database snapshots |
| `snapshots.table` | `ALERTSTREAM_SNAPSHOTS_TABLE` | `alertstream_snapshots` | Database table name |
| `snapshots.retention_days` | `ALERTSTREAM_SNAPSHOTS_RETENTION` | `30` | Days before prune-eligible |
| `snapshots.dedup_minutes` | `ALERTSTREAM_SNAPSHOTS_DEDUP_MINUTES` | `60` | Dedup window (0 = disabled) |
| `snapshots.route_prefix` | `ALERTSTREAM_SNAPSHOTS_ROUTE_PREFIX` | `alertstream` | URL prefix |
| `snapshots.route_middleware` | _(config only)_ | `['web']` | Middleware for snapshot routes |

## Package Structure

```
src/
├── AlertChannels/
│   ├── Contracts/
│   │   └── AlertChannel.php          <- implement this to add any channel
│   ├── AlertStreamNotificationChannel.php
│   ├── SlackChannel.php
│   ├── TeamsChannel.php
│   ├── DiscordChannel.php
│   └── MailChannel.php
├── Commands/
│   ├── TestAlertCommand.php
│   └── PruneSnapshotsCommand.php
├── Events/
│   └── ExceptionCaptured.php
├── Exceptions/
│   ├── AlertStreamException.php
│   └── Handler.php
├── Http/
│   └── Controllers/
│       ├── HealthController.php
│       └── SnapshotController.php
├── Listeners/
│   └── SendExceptionToAlertStream.php
├── Models/
│   └── Snapshot.php
├── Providers/
│   └── AlertStreamServiceProvider.php
└── Services/
    ├── AlertStreamService.php
    ├── SnapshotService.php
    └── ThrottleService.php

database/
└── migrations/
    └── create_alertstream_snapshots_table.php

resources/
└── views/
    └── snapshots/
        ├── index.blade.php
        └── show.blade.php

routes/
└── alertstream.php
```

## Local Development

Test the package locally in another project using path repositories (changes are reflected instantly via symlink):

```bash
# In your test Laravel app
composer config repositories.alertstream path /path/to/laravel-alertstream
composer require nightshift-foundry/laravel-alertstream:*@dev
php artisan vendor:publish --tag=alertstream-config
php artisan alertstream:test
```

## Development

```bash
composer test         # run tests
composer lint         # check code style
composer lint:fix     # auto-fix code style
```

The pre-commit hook runs `php-cs-fixer` automatically on every commit (installed via `composer install`).

## License

MIT - see [LICENSE](LICENSE).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).