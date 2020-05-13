#!/usr/bin/env bash


# CHECK FOR XDEBUG
if [ ! -z "${INCLUDE_XDEBUG}" ]; then
  if [ "${INCLUDE_XDEBUG}" == "true" ]; then
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "Xdebug installed";
  fi
fi
