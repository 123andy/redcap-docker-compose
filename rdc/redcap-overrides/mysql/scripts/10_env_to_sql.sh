#!/usr/bin/env bash

echo "==> MySql Server Script Updates"

# Mysql8 uses a different password plugin (caching_sha2_password) instead of the old
# mysql_native_password.  As of 10/2019 the mysqli interface in php doesn't support sha2 logins, so for
# REDCap (and PHP) to be able to log in, we need to change the password plugin.

# This allows the following mysql commands to not throw a warning about using a password on the command line
export MYSQL_PWD=${MYSQL_ROOT_PASSWORD}

echo "Update MYSQL_USER password for native login"
mysql -u root -e "ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';"
echo "Update ROOT password for native login"
mysql -u root -e "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';"

# Set Timezone
echo "Set Timezone to: ${TZ}"
mysql -u root -e "SET GLOBAL time_zone = '${TZ}';"

echo "==> MySql Server Script Updates DONE"