#!/usr/bin/env bash

echo "Fix Permissions on web user homedir: ${APACHE_RUN_HOME}"
chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $APACHE_RUN_HOME
