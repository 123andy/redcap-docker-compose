#!/usr/bin/env bash


# CHANGE THE WWW-DATA UID TO MATCH CLIENT ID IF SET
if [ ! -z "${APACHE_RUN_USER_ID}" ]; then
  #usermod -u $APACHE_RUN_USER www-data
  usermod --non-unique --uid ${APACHE_RUN_USER_ID} www-data
fi
