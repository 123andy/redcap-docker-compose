#!/usr/bin/env bash

# SET THE ENV VARIABLE TO MODIFY THE SSMTP MAIL RELAY
if [ ! -z "${SSMTP_MAILHUB}" ]; then
  # Replace the line in the ssmtp config
  sed -i "s/mailhub=.*/mailhub=${SSMTP_MAILHUB}/g" /etc/ssmtp/ssmtp.conf
  echo "SSMTP set to ${SSMTP_MAILHUB}"
fi
