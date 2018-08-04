#!/usr/bin/env sh

set -e

# CURRENTLY WE ARE ROOT AND NEED TO GRANT MAILHOG ACCESS TO THIS SHARED PATH
if [ ! -z "$MH_MAILDIR_PATH" ]; then
  echo "Verifying permissions of $MH_MAILDIR_PATH"
  mkdir -p $MH_MAILDIR_PATH
  chown -R 1000 $MH_MAILDIR_PATH
fi

if [ ! -z "$MH_OUTGOING_SMTP" ]; then
  echo "Verifying permissions of outgoing smtp config at $MH_OUTGOING_SMTP"
  chown 1000 $MH_OUTGOING_SMTP
fi

# RUN MAILHOG AS A NON-PRIV USER
su - mailhog
exec "MailHog"