#!/usr/bin/env bash

# SEE IF WE SHOULD REMOVE THE DEFAULT VHOST
# THIS MIGHT BE DESIRED IF YOU WANT TO USE A CUSTOM VIRTUAL HOST CONFIGURATION
if [[ "${REMOVE_DEFAULT_VHOST}" = true ]]; then
    rm /etc/apache2/sites-enabled/000-default.conf
    echo "Removed 000-default site"
fi
