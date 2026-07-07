#!/bin/sh
set -e

PORT="${PORT:-8080}"
exec php -S "0.0.0.0:${PORT}" -t public public/index.php
