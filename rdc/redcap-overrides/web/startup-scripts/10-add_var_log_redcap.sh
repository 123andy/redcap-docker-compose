#!/usr/bin/env bash

# TO MIMIC OUR INTERNAL SERVERS, MAKE /var/log/redcap A SYM LINK TO /var/log/apache2
if [[ ! -e "/var/log/redcap" ]]; then
    ln -s /var/log/apache2 /var/log/redcap
    echo "Linking /var/log/redcap to apache logs"
fi