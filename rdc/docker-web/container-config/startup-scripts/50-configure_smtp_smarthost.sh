#!/usr/bin/env bash

# SET THE ENV VARIABLE TO MODIFY THE SSMTP MAIL RELAY
if [ ! -z "${SMTP_SMARTHOST}" ]; then
  # Replace the line in the msmtp config
  sed -i "s/^host\s.*/host ${SMTP_SMARTHOST}/g" /etc/msmtprc
  echo "MSMTP host set to ${SMTP_SMARTHOST}"
fi

if [ ! -z "${SMTP_PORT}" ]; then
  # Replace the line in the msmtp config
  sed -i "s/^port\s.*/port ${SMTP_PORT}/g" /etc/msmtprc
  echo "MSMTP host set to ${SMTP_PORT}"
fi


