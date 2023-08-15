FROM php:8.2-apache-buster

## https://hub.docker.com/_/php/tags?page=1&name=apache

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get -yq --no-install-recommends install \
    msmtp-mta \
    ca-certificates \
    git \
    vim \
    ssh \
    wget \
    libpng-dev \
    libzip-dev \
    zip \
    ghostscript \
    libmagickwand-dev \
# INSTALL IMAGICK
    && pecl install imagick \
    && docker-php-ext-enable imagick \
# INSTALL MYSQLI AND OTHER DOCKER FUN
    && docker-php-ext-install gd zip mysqli \
# INSTALL XDEBUG
    && pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > "/usr/local/etc/php/conf.d/xdebug.ini" \
### cleanup
    && rm -r /var/lib/apt/lists/*

# Update ImageMagick policy
RUN sed -i 's/policy\ domain=\"coder\" rights=\"none\" pattern=\"PDF\"/policy domain=\"coder\" rights=\"read\" pattern=\"PDF\"/g' /etc/ImageMagick-6/policy.xml

# install and configure x-debug 3 when running for first time
#RUN yes | pecl install xdebug \
#    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini

# COPY OUR DEFAULT CONTAINER CONFIG
COPY ./container-config /etc/container-config
# THESE CONFIG FILES CAN BE SUPPLEMENTED OR OVERWRITTEN BY THE container_start.sh SCRIPT
# BY MOUNTING ADDITIONAL FILES INTO /etc/container-config-overwrite FOLDER.  THIS FOLDER
# IS ALIASED AS THE `WEB_OVERRIDES` ENV VARIABLE IN THE .env FILE

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
