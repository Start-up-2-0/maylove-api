#!/bin/sh
set -e

PORT="${PORT:-8080}"
echo "[maylove-api] starting PHP server on 0.0.0.0:${PORT}"

( /usr/local/bin/migrate.sh || echo "[maylove-api] WARN: migrations on start failed" ) &

exec php -S "0.0.0.0:${PORT}" -t public public/router.php
