#!/bin/bash
# setup-test-app.sh
# Quick setup script for testing AlertStream locally
#
# Usage:
#   ./setup-test-app.sh                          # default: ../laravel-alertstream-test (sibling dir)
#   ./setup-test-app.sh /path/to/test-app        # custom path
#
# What it does:
#   1. Creates a fresh Laravel app in a sibling directory (laravel-alertstream-test)
#   2. Links AlertStream as a local path repository (symlinked — edits are instant)
#   3. Installs Laravel Sail with MySQL + Redis
#   4. Publishes config & logging channel
#   5. Enables snapshots, throttling, queue (Redis), and the health check endpoint
#   6. Starts Sail, runs migrations, and verifies the installation

set -e

PACKAGE_PATH="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="${1:-$(dirname "$PACKAGE_PATH")/laravel-alertstream-test}"

echo ""
echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║           🚀 AlertStream — Local Test App Setup                    ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""
echo "  Package path : $PACKAGE_PATH"
echo "  Test app path: $PROJECT_DIR"
echo ""

# ── Step 1: Create Laravel app ──────────────────────────────────────────

if [ -d "$PROJECT_DIR/vendor" ]; then
    echo "⚠️  Directory already exists. Remove it first? (y/N)"
    read -r CONFIRM
    if [[ "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "🗑️  Removing $PROJECT_DIR ..."
        rm -rf "$PROJECT_DIR"
    else
        echo "Aborting."
        exit 1
    fi
fi

echo "📦 Creating fresh Laravel application..."
composer create-project laravel/laravel "$PROJECT_DIR" --no-interaction --quiet
cd "$PROJECT_DIR"

# ── Step 2: Link AlertStream as path repository ────────────────────────

echo "🔗 Adding AlertStream as local path repository..."
composer config repositories.alertstream path "$PACKAGE_PATH"

echo "📥 Installing AlertStream package..."
composer require nightshift-foundry/laravel-alertstream:*@dev --no-interaction

# ── Step 2b: Install Laravel Sail ──────────────────────────────────────

echo "⛵ Installing Laravel Sail..."
composer require laravel/sail --dev --no-interaction --quiet

# On Apple Silicon, replace the vendor Dockerfile BEFORE sail:install auto-builds.
# Sail's default Dockerfile uses the Ondrej PPA which only publishes amd64 packages.
# Our bundled Dockerfile uses the official php:8.4 image which is natively multi-arch.
if [ "$(uname -m)" = "arm64" ]; then
    echo "🍎 Apple Silicon detected — injecting native arm64 Dockerfile..."
    cp "$PACKAGE_PATH/docker/sail/Dockerfile" vendor/laravel/sail/runtimes/8.4/Dockerfile
fi

# sail:install generates compose.yaml and triggers a build using the runtimes/8.4 context.
# On arm64 that context now contains our custom Dockerfile, so the build will succeed.
php artisan sail:install --with=mysql,redis,mailpit --php=8.4 --quiet 2>/dev/null \
    || php artisan sail:install --with=mysql,redis,mailpit --quiet

# Detect compose filename (newer Sail uses compose.yaml, older uses docker-compose.yml)
if [ -f "compose.yaml" ]; then
    COMPOSE_FILE="compose.yaml"
elif [ -f "docker-compose.yml" ]; then
    COMPOSE_FILE="docker-compose.yml"
else
    COMPOSE_FILE="compose.yaml"
fi

# Mount the package source into the container so the Composer symlink resolves
# The symlink in vendor/ points to the absolute host path — we mirror it inside Docker
if ! grep -q "$PACKAGE_PATH" "$COMPOSE_FILE" 2>/dev/null; then
    # Adds to the first volumes: section (laravel.test service)
    sed -i '' "/'\.:/a\\
\\            - ${PACKAGE_PATH}:${PACKAGE_PATH}:cached" "$COMPOSE_FILE" 2>/dev/null || true
fi

# ── Step 3: Publish config & logging channel ───────────────────────────

echo "📋 Publishing configuration..."
php artisan vendor:publish --tag=alertstream-config --force --quiet
php artisan vendor:publish --tag=alertstream-logging --force --quiet

# ── Step 4: Configure .env ─────────────────────────────────────────────

echo "⚙️  Writing AlertStream env vars..."
cat >> .env <<'ENV'

# ─── AlertStream ────────────────────────────────────────────────────────
ALERTSTREAM_ENABLED=true
ALERTSTREAM_REPORT_EXCEPTIONS=true
ALERTSTREAM_LOG_CHANNELS=single

# Queue — uses Redis provided by Sail
ALERTSTREAM_QUEUE=true
ALERTSTREAM_QUEUE_CONNECTION=redis
ALERTSTREAM_QUEUE_NAME=alertstream

# Throttling
ALERTSTREAM_THROTTLE=true
ALERTSTREAM_THROTTLE_MAX=5
ALERTSTREAM_THROTTLE_COOLDOWN_MINUTES=60

# Snapshots — persists exceptions to DB with a viewable URL
ALERTSTREAM_SNAPSHOTS=true
ALERTSTREAM_SNAPSHOTS_RETENTION=30
ALERTSTREAM_SNAPSHOTS_DEDUP_MINUTES=60
ALERTSTREAM_SNAPSHOTS_ROUTE_PREFIX=alertstream

# Channels — uncomment and supply webhooks to activate
# ALERTSTREAM_CHANNELS=slack,discord,teams,mail
# ALERTSTREAM_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
# ALERTSTREAM_DISCORD_WEBHOOK=https://discord.com/api/webhooks/YOUR/WEBHOOK
# ALERTSTREAM_TEAMS_WEBHOOK=https://your-tenant.webhook.office.com/webhookb2/...
# ALERTSTREAM_MAIL_TO=alerts@your-company.com
# ALERTSTREAM_MAIL_FROM=noreply@your-company.com

# Mail — Mailpit (provided by Sail, catches all outbound mail)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="alertstream@localhost"
MAIL_FROM_NAME="${APP_NAME}"
ENV

# ── Step 5: Start Sail & run migrations ────────────────────────────────

echo "⛵ Starting Sail containers (building image on first run — may take a few minutes)..."
./vendor/bin/sail up -d

echo "⏳ Waiting for MySQL to be ready..."
until ./vendor/bin/sail exec mysql mysqladmin ping --silent 2>/dev/null; do
    sleep 2
done

echo "🗄️  Running migrations (includes alertstream_snapshots table)..."
./vendor/bin/sail artisan migrate --quiet

# ── Step 6: Verify installation ────────────────────────────────────────

echo ""
echo "🧪 Running test alert..."
./vendor/bin/sail artisan alertstream:test 2>&1 && echo "" || true

echo "🧪 Running test log (debug level)..."
./vendor/bin/sail artisan alertstream:test --type=debug 2>&1 && echo "" || true

# ── Step 7: Verify symlink ─────────────────────────────────────────────

echo "🔗 Verifying symlink..."
SYMLINK=$(ls -la vendor/nightshift-foundry/laravel-alertstream 2>/dev/null | grep -- '->' || true)
if [ -n "$SYMLINK" ]; then
    echo "   ✅ $SYMLINK"
else
    echo "   ⚠️  Symlink not detected — changes to the package may require composer update"
fi

# ── Done ───────────────────────────────────────────────────────────────

echo ""
echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                       ✅ Setup Complete!                           ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""
echo "📍 Test app: $PROJECT_DIR"
echo "⛵ Sail is running — containers: mysql, redis, laravel"
echo ""
echo "── Quick test commands ──────────────────────────────────────────────"
echo ""
echo "  cd $PROJECT_DIR"
echo ""
echo "  # Report (exception alert → log channels + notification channels)"
echo "  ./vendor/bin/sail artisan alertstream:test"
echo ""
echo "  # Log at any level (→ log channels only, no notifications)"
echo "  ./vendor/bin/sail artisan alertstream:test --type=debug"
echo "  ./vendor/bin/sail artisan alertstream:test --type=info"
echo "  ./vendor/bin/sail artisan alertstream:test --type=warning"
echo "  ./vendor/bin/sail artisan alertstream:test --type=error"
echo ""
echo "  # Test a specific notification channel"
echo "  ./vendor/bin/sail artisan alertstream:test slack"
echo "  ./vendor/bin/sail artisan alertstream:test discord"
echo ""
echo "  # Start the queue worker (processes AlertStream alerts)"
echo "  ./vendor/bin/sail artisan queue:work --queue=alertstream"
echo ""
echo "  # Health check endpoint"
echo "  curl http://localhost/alertstream/health"
echo ""
echo "  # View snapshots"
echo "  open http://localhost/alertstream/snapshots"
echo ""
echo "  # View captured mail (Mailpit web UI)"
echo "  open http://localhost:8025"
echo ""
echo "  # Prune old snapshots"
echo "  ./vendor/bin/sail artisan alertstream:prune-snapshots"
echo "  ./vendor/bin/sail artisan alertstream:prune-snapshots --days=7"
echo ""
echo "  # Watch the log"
echo "  ./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log"
echo ""
echo "── Sail commands ───────────────────────────────────────────────────"
echo ""
echo "  ./vendor/bin/sail up -d          # start containers"
echo "  ./vendor/bin/sail down           # stop containers"
echo "  ./vendor/bin/sail shell          # shell into app container"
echo ""
echo "── Package development ─────────────────────────────────────────────"
echo ""
echo "  Package source (symlinked — changes are instant):"
echo "  $PACKAGE_PATH/src/"
echo ""
echo "  Run package tests:"
echo "  cd $PACKAGE_PATH && composer test"
echo ""

