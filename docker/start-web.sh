#!/bin/sh
set -e

PORT="${PORT:-8080}"
echo "[maylove-api] starting PHP server on 0.0.0.0:${PORT}"

php -S "0.0.0.0:${PORT}" -t public public/index.php &
SERVER_PID=$!

# Migrations pendentes (pre-deploy já roda; isto é fallback sem bloquear a PORT).
/usr/local/bin/migrate.sh || echo "[maylove-api] WARN: migrations on start failed (see logs)"

wait "$SERVER_PID"
