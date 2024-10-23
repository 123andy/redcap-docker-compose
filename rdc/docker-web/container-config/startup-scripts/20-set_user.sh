#!/usr/bin/env bash

# CHANGE THE WWW-DATA UID TO MATCH CLIENT ID IF SET
if [ ! -z "${APACHE_RUN_USER_ID}" ]; then
  result=$(id -u www-data)
  if [ "$result" != "${APACHE_RUN_USER_ID}" ]; then
    #usermod -u $APACHE_RUN_USER www-data
    usermod --non-unique --uid ${APACHE_RUN_USER_ID} www-data
    echo "Changing apache user from $result to ${APACHE_RUN_USER_ID}"
  else
    echo "APACHE_RUN_USER already set"
  fi
fi
