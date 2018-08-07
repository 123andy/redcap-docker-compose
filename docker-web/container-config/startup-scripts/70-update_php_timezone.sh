#!/usr/bin/env bash

# UPDATE PHP TIMEZONE IF SPECIFIED IN ENV FILE
if [ ! -z "$TZ" ]; then
  sed -i "s#date.timezone.*#date.timezone\ =\ $TZ#g" /usr/local/etc/php/conf.d/*
  echo "Updated timezone $TZ"
else
  echo "$TZ IS NOT DEFINED"
fi
