#!/bin/sh

set -e

# ADD USERID TO MATCH APACHE
if [ ! -z "$APACHE_RUN_USER_ID" ]; then
    if [ $(getent passwd "${APACHE_RUN_USER_ID}" | cut -d: -f1) ]; then
        echo "User ID ${APACHE_RUN_USER_ID} already exists"
    else
        echo "Creating user ID ${APACHE_RUN_USER_ID} as www-data"
        adduser -D -H -u "$APACHE_RUN_USER_ID" www-data
    fi
fi

if grep -Fq "cron.php" /var/spool/cron/crontabs/root
then
    # code if found
	echo "Found cron.php entry already exists"
else
	# ADD REDCap CRON ENTRIES
	echo "*   * * * *   wget web${REDCAP_WEBROOT_PATH}cron.php --spider >/dev/null 2>&1" >> /var/spool/cron/crontabs/root

	# Add logrotate scripts
	echo "*/5 * * * *   /usr/sbin/logrotate /etc/logrotate.conf"    >> /var/spool/cron/crontabs/root
	echo "Added cron entry"
fi

# Log Rotate throws an error if this file doesn't exist
touch /var/log/messages

# Start up the cronjobs
/usr/sbin/crond -f /var/spool/cron/crontabs
