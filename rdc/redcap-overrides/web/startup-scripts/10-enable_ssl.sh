#!/usr/bin/env bash
if [[ "$WEB_ENABLE_SSL_SITE" = true ]]; then
    echo "Enabling SSL Site"

    echo 'Enabling SSL module'
    a2enmod ssl

    echo 'Enabling ssl.conf vhost'
    a2ensite ssl

    caSource="/var/credentials/rdc_rootCA.pem"
    caDest="/usr/local/share/ca-certificates/rdc_rootCA.crt"
    if [[ -f "${caSource}" ]]; then
        if [[ ! -f "${caDest}" ]]; then
            echo "Installing rootCA..."
            cp "${caSource}" "${caDest}"
            update-ca-certificates
        else
            echo "rootCA already installed"
        fi
    else
        echo "Skipping rootCA installation - ${caSource} missing"
    fi
fi

