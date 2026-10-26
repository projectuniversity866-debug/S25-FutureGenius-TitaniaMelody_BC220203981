#!/usr/bin/env bash
#
# AQDesk — deploy or update on the server (roadmap 5.1).
#
# Run from src/ after the first-time setup in docs/DEPLOYMENT.md:
#   bash deploy.sh            # pull main, install, migrate, cache, restart workers
#   BRANCH=development bash deploy.sh
#
# Node.js is not needed: the built assets in public/build are committed.

set -euo pipefail
cd "$(dirname "$0")"

BRANCH="${BRANCH:-main}"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

[ -f .env ] || { echo "No .env — copy .env.production.example to .env and fill it in first."; exit 1; }

echo "→ Maintenance mode"
"$PHP" artisan down --retry=30 || true
trap '"$PHP" artisan up' EXIT

echo "→ Pull $BRANCH"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

echo "→ PHP packages (no dev tools)"
"$COMPOSER" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

grep -q '^APP_KEY=base64:' .env || "$PHP" artisan key:generate --force

echo "→ Database"
"$PHP" artisan migrate --force

echo "→ Storage link"
[ -e public/storage ] || "$PHP" artisan storage:link

echo "→ Caches (config, routes, views, events)"
"$PHP" artisan optimize

echo "→ Restart queue workers so they load the new code"
"$PHP" artisan queue:restart

echo "Done. Health check: \$APP_URL/up"
