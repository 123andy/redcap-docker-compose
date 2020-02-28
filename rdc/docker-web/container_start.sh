#!/usr/bin/env bash

# PASS VARIABLES TO APACHE?
. /etc/apache2/envvars

# AS PART OF A DOCKER-COMPOSE YOU CAN AUGMENT THE DEFAULTS WITH THE OVERRIDE DIRECTORY
# MAP A VOLUME TO /etc/container-config-override AND THEY WILL AUGMENT/REPLACE EXISTING CONFIGS
# ADD A SCRIPT TO THE /etc/container-config/startup-scripts FOLDER TO DO MORE
[ -d /etc/container-config-override ] && cp -RT /etc/container-config-override /etc/container-config

# DEFAULT TO THE DOCKER DEFAULT CONFIG DIRECTORY
[ -d /etc/container-config/shibboleth ]      && cp -RT /etc/container-config/shibboleth  /etc/shibboleth
[ -d /etc/container-config/apache2 ]         && cp -RT /etc/container-config/apache2     /etc/apache2
[ -d /etc/container-config/php ]             && cp -RT /etc/container-config/php         /usr/local/etc/php/conf.d
[ -d /etc/container-config/msmtp ]           && cp -RT /etc/container-config/msmtp       /etc/

# RUN STARTUP SCRIPTS
[ -d /etc/container-config/startup-scripts ] && for cmds in /etc/container-config/startup-scripts/*.sh ; do . ${cmds} ; done

# START APACHE
mkdir -p /var/log/apache2
exec /usr/sbin/apache2 -DFOREGROUND
