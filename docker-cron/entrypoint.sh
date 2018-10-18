#!/usr/bin/env sh

# command: ['crond', '-f', '/var/spool/cron/crontabs']
#set -e
#
#if [ ! -z "$MH_MAILDIR_PATH" ]; then
#  echo "Verifying permissions of $MH_MAILDIR_PATH"
#  mkdir -p $MH_MAILDIR_PATH
#  chown -R 1000 $MH_MAILDIR_PATH
#fi


exec crond -f /var/spool/cron/crontabs
