#!/usr/bin/env bash


# CHECK FOR DEFAULT PHP INI
if [ ! -z "${PHP_DEFAULT_INI}" ]; then
  if [ "${PHP_DEFAULT_INI,,}" == "prod" ]; then

#    cp "/usr/local/lib/$PWD/config/redcap_php.ini" "/usr/local/etc/php/conf.d"
    echo "PHP using production php.ini";
  fi

  if [ "${PHP_DEFAULT_INI,,}" == "dev" ]; then
#    cp "/usr/local/lib/$PWD/config/redcap_php.ini" "/usr/local/etc/php/conf.d"
    echo "PHP using development php.ini";
  fi
fi
