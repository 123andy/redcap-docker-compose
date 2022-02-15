FROM php:8.1.3RC1-apache-buster
## https://hub.docker.com/_/php/

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get -yq --no-install-recommends install \
    msmtp-mta \
    ca-certificates \
    git \
    ssh \
    wget \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-install gd zip mysqli \
    ### cleanup \
    && rm -r /var/lib/apt/lists/*

# install and configure x-debug 3 when running for first time
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini

# MOUNT A VOLUME WITH OUR DEFAULT CONTAINER CONFIG
# THIS CAN BE OVERWRITTEN BY MOUNTING TO THE /etc/container-config-overwrite FOLDER
COPY ./container-config /etc/container-config

ENV SERVER_NAME localhost
ENV SERVER_ADMIN root
ENV SERVER_ALIAS localhost
ENV APACHE_RUN_HOME /var/www
ENV APACHE_DOCUMENT_ROOT /var/www/html
ENV APACHE_ERROR_LOG /dev/stdout
ENV APACHE_ACCESS_LOG /dev/stdout
ENV SMTP_SMARTHOST mailhog

# If you are overriding this container, you might want to remove the default vhost
ENV REMOVE_DEFAULT_VHOST false

# Copy main startup script
COPY container_start.sh /etc/container_start.sh

RUN chmod +x /etc/container_start.sh
CMD ["/etc/container_start.sh"]
