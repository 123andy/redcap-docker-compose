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

# ADD REDCap CRON ENTRIES
echo "*   * * * *   wget web${REDCAP_WEBROOT_PATH}cron.php --spider >/dev/null 2>&1" >> /var/spool/cron/crontabs/root

# Add logrotate scripts
echo "*/5 * * * *   /usr/sbin/logrotate /etc/logrotate.conf"    >> /var/spool/cron/crontabs/root
# Log Rotate throws an error if this file doesn't exist
touch /var/log/messages

# Start up the cronjobs
/usr/sbin/crond -f /var/spool/cron/crontabs
