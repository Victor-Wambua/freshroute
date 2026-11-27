#!/bin/sh
# Builds a throwaway .env.e2e (gitignored) with a fresh APP_KEY and a separate SQLite file. Never touches the dev database.
set -e
cd "$(dirname "$0")/.."
KEY=$(php -r 'echo "base64:".base64_encode(random_bytes(32));')
cat > .env.e2e <<ENVEOF
APP_NAME=FreshRoute
APP_ENV=e2e
APP_KEY=$KEY
APP_DEBUG=false
APP_URL=http://localhost:4362
DB_CONNECTION=sqlite
DB_DATABASE=$(pwd)/database/e2e.sqlite
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
LOG_CHANNEL=single
FR_SHOW_SYNTHETIC_BANNER=true
ENVEOF
rm -f database/e2e.sqlite && touch database/e2e.sqlite
APP_ENV=e2e php artisan migrate:fresh --seed --force --no-interaction > /dev/null
