#!/usr/bin/env bash

# TO MIMIC OUR INTERNAL SERVERS, MAKE /var/log/redcap A SYM LINK TO /var/log/apache2
ln -s /var/log/apache2 /var/log/redcap
