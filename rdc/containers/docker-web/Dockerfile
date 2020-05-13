FROM php:7.3-apache
# I see there is now php:7.3-apache and 7.4 is a release candidate
## https://hub.docker.com/_/php/

MAINTAINER andy123@stanford.edu
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get -yq --no-install-recommends install \
    msmtp-mta \
    ca-certificates \
    git \
    libpng-dev \
    libzip-dev \
#    zip \
    && docker-php-ext-configure zip --with-libzip \
    && docker-php-ext-install gd zip mysqli \
    ### cleanup \
    && rm -r /var/lib/apt/lists/*

# install and configure x-debug when running for first time then create xdebug.ini
RUN yes | pecl install xdebug
# Should we run xdebug
# RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
#    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini


ENV INCLUDE_XDEBUG true

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
ENV REMOVE_DEFAULT_VHOST false

# APACHE_RUN_USER APACHE_RUN_GROUP

# Copy main startup script
COPY container_start.sh /etc/container_start.sh
RUN chmod +x /etc/container_start.sh
CMD ["/etc/container_start.sh"]
