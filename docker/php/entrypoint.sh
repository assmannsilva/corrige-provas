#!/bin/sh

set -e

as_root() {
    [ "$(id -u)" = "0" ]
}

fix_permissions() {
    if as_root && [ -d storage ]; then
        chown -R www-data:www-data storage bootstrap/cache
    fi
}

fix_permissions

case "$1" in
    -*)
        set -- php "$@"
        ;;
esac

if [ "$1" = "php-fpm" ]; then
    exec "$@"
fi

set +e
"$@"
status=$?
set -e

fix_permissions

exit "$status"
