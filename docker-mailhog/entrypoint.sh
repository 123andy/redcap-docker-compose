#!/usr/bin/env sh

set -e

# CURRENTLY WE ARE ROOT AND NEED TO GRANT MAILHOG ACCESS TO THIS SHARED PATH
if [ ! -z "$MH_MAILDIR_PATH" ]; then
  echo "Verifying permissions of $MH_MAILDIR_PATH"
  mkdir -p $MH_MAILDIR_PATH
  chown -R 1000 $MH_MAILDIR_PATH
fi

# RUN MAILHOG AS A NON-PRIV USER
su - mailhog
exec "MailHog"