# Shared by the e2e scripts: start AQDesk on a throw-away SQLite database.
# Your MySQL data is never touched.
#
#   source tests/e2e/server.sh && start_server     # sets $B, $WORK, $PHP
#
# The server is stopped automatically when the calling script exits.

PHP="${PHP:-/c/wamp64/bin/php/php8.3.28/php.exe}"
[ -x "$PHP" ] || PHP=php
PORT="${PORT:-8765}"
B="http://127.0.0.1:$PORT"
WORK="storage/framework/testing/e2e"

start_server() {
    local db
    db="$(pwd)/$WORK/e2e.sqlite"

    mkdir -p "$WORK"
    rm -f "$db" "$WORK"/*.jar "$WORK"/*.html "$WORK/php-errors.txt"
    touch "$db"

    export APP_ENV=local APP_DEBUG=true DB_CONNECTION=sqlite DB_DATABASE="$db" \
           SESSION_DRIVER=file CACHE_STORE=array QUEUE_CONNECTION=sync MAIL_MAILER=log

    echo "PHP $("$PHP" -r 'echo PHP_VERSION;') · fresh database · seeding…"
    "$PHP" artisan migrate:fresh --seed --force -q || { echo "FAIL migrate"; exit 1; }

    # Laravel's router script expects to be started from inside public/.
    (cd public && exec "$PHP" -S "127.0.0.1:$PORT" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php) \
        > "$WORK/server.log" 2>&1 &
    SERVER=$!
    trap 'kill $SERVER 2>/dev/null' EXIT

    for _ in $(seq 1 30); do curl -s -o /dev/null "$B/up" && return 0; sleep 0.3; done
    echo "FAIL server did not start"; exit 1
}
