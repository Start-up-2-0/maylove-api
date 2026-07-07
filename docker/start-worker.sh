#!/bin/sh
set -e

exec php bin/console messenger:consume async -vv --time-limit=3600
