#!/usr/bin/env bash

# IF THE WEBROOT IS EMPTY - THEN COPY OVER THE DEFAULT FILES
if [ -z "$(ls -A ${APACHE_DOCUMENT_ROOT})" ]; then
    echo "Setting webroot with default content"
    cp -RT /etc/container-config/webroot ${APACHE_DOCUMENT_ROOT}
fi
