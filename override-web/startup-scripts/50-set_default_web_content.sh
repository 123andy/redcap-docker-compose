#!/usr/bin/env bash

# IF THE WEBROOT IS EMPTY - THEN COPY OVER THE DEFAULT FILES
#if [ -z "$(ls -A ${APACHE_DOCUMENT_ROOT})" ]; then
    echo "Setting webroot with default content"
    rm -f /etc/container-config/webroot/index.php
    cp -RT /etc/container-config/webroot ${APACHE_DOCUMENT_ROOT}
#fi
