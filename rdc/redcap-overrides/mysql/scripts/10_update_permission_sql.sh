#!/usr/bin/env bash

# ALTER THE PASSWORD PLUGIN FOR THE REDCAP AND ROOT USERS WITH MYSQL8
echo "ALTER USER '${MYSQL_USER}'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_PASSWORD}';" >> 20_mysql8_permission_updates.sql
echo "ALTER USER 'root'@'%' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASSWORD}';" >> 20_mysql8_permission_updates.sql
echo "SET GLOBAL time_zone = '${TZ}';" >> 30_update_timezone.sql