#!/bin/sh
set -e

/usr/local/bin/migrate.sh

PORT="${PORT:-8080}"
exec php -S "0.0.0.0:${PORT}" -t public public/index.php
