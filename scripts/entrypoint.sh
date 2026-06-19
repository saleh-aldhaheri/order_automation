#!/bin/bash
set -euo pipefail

# Local development entrypoint: prepares the app and runs `php artisan serve`.
# The application directory is bind-mounted, so code changes are picked up live.

APP_DIR="${APP_DIR:-/var/www/html}"
SERVE_HOST="${SERVE_HOST:-0.0.0.0}"
SERVE_PORT="${SERVE_PORT:-8000}"

function main() {
    cd "${APP_DIR}"
    installDependencies
    prepareStorage
    runMigrations
    setDBStates
    clearCaches
    serve
}

# Install composer deps only when missing (vendor/ is bind-mounted, so usually
# already present from the host).
function installDependencies() {
    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist
    fi
}

function prepareStorage() {
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
    chmod -R 775 storage bootstrap/cache || true
    php artisan storage:link --force || true
}

function runMigrations() {
    php artisan migrate --force
}

function setDBStates() {
    php artisan app:ensure-database-state-command
}

# Clear (do NOT cache) so config/routes/views stay live during development.
function clearCaches() {
    php artisan optimize:clear
}

function serve() {
    exec php artisan serve --host="${SERVE_HOST}" --port="${SERVE_PORT}"
}

main
