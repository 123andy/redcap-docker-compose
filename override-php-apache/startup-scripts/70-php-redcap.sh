#!/usr/bin/env bash

# UPDATE PHP TIMEZONE IF SPECIFIED IN ENV FILE
if [ ! -z "$TZ" ]; then
  sed -i "s#date.timezone.*#date.timezone\ =\ $TZ#g" /usr/local/etc/php/conf.d/*
  echo "Updated timezone $TZ"
else
  echo "$TZ IS NOT DEFINED"
fi


#
## UPDATE PHP TIMEZONE IF SPECIFIED IN ENV FILE
#if [ ! -z "$TZ" ]; then
#    #/var/log/apache2/php_error.log
#  sed -i "s#error_log.*#error_log\ =\ $TZ#g" /usr/local/etc/php/conf.d/*
#  echo "Updated timezone $TZ"
#else
#  echo "$TZ IS NOT DEFINED"
#fi
