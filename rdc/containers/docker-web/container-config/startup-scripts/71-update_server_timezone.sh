#!/usr/bin/env bash

# UPDATE SERVER TIMEZONE IF SPECIFIED IN ENV FILE
if [ ! -z "$TZ" ]; then
  ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
fi
