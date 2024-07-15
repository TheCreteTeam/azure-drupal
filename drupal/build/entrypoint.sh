#!/bin/sh
set -e
service ssh start
#tail -f /dev/null

/usr/sbin/sshd

# Call the original Docker PHP entrypoint script
exec docker-php-entrypoint "$@"