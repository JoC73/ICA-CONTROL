#!/usr/bin/env sh
set -e

php artisan queue:work --sleep=3 --tries=3 --timeout=90
