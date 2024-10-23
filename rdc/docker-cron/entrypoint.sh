#!/bin/sh

set -e

# ADD USERID TO MATCH APACHE
if [ ! -z "$APACHE_RUN_USER_ID" ]; then
  echo "IN HERE $APACHE_RUN_USER_ID"
    if [ $(getent passwd "${APACHE_RUN_USER_ID}" | cut -d: -f1) ]; then
        getent passwd "${APACHE_RUN_USER_ID}" | cut -d: -f1
        echo "User ID ${APACHE_RUN_USER_ID} already exists"
    else
        echo "Creating user ID ${APACHE_RUN_USER_ID} as cron-www-data"
        adduser --disabled-password --gecos --no-create-home --ingroup www-data --uid "$APACHE_RUN_USER_ID" cron-www-data
    fi
fi

# Insert crontabs while substituting for webroot_path
sed -i 's|REDCAP_WEBROOT_PATH|'$REDCAP_WEBROOT_PATH'|g' /var/spool/cron/crontabs/root

#if grep -Fq "cron.php" /var/spool/cron/crontabs/root
#then
#    # code if found
#	echo "Found cron.php entry already exists"
#else
#	# ADD REDCap CRON ENTRIES
#	echo "*   * * * *   wget web${REDCAP_WEBROOT_PATH}cron.php --spider >/dev/null 2>&1" > /var/spool/cron/crontabs/root
#	echo "Added redcap every minute cron entry"
#
#	# Add logrotate scripts
#	echo "*/5 * * * *   /usr/sbin/logrotate /etc/logrotate.conf"    >> /var/spool/cron/crontabs/root
#	echo "Added logrotate to cron tab"
#fi

# Log Rotate throws an error if this file doesn't exist
touch /var/log/messages

# Start up the cronjobs
/usr/sbin/crond -f /var/spool/cron/crontabs
