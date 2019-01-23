#!/bin/sh

set -e

# ADD USERID TO MATCH APACHE
if [ ! -z "$APACHE_RUN_USER_ID" ]; then
    if [ $(id -u $APACHE_RUN_USER_ID >/dev/null 2>&1) ]; then
        echo "User ${APACHE_RUN_USER_ID} already exists"
    else
        echo "Adding user ${APACHE_RUN_USER_ID}"
        adduser -D -H -u "$APACHE_RUN_USER_ID" www-data
    fi
fi

# Replace REDCAP_WEBROOT_PATH for mounted crontab
sed -i "s/REDCAP_WEBROOT_PATH/${REDCAP_WEBROOT_PATH}/g" /var/spool/cron/crontabs/root

# Log Rotate throws an error if this file doesn't exist
touch /var/log/messages

# Start up the cronjobs
/usr/sbin/crond -f /var/spool/cron/crontabs
