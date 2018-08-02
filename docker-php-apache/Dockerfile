FROM php:7.2-apache
## https://hub.docker.com/_/php/

MAINTAINER andy123@stanford.edu
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -qq && \
    apt-get -yq --no-install-recommends install \
#    libapache2-mod-shib2 \
    ssmtp \
    vim \
    emacs \
    git \
    ssh \
    wget \
    ### cleanup \
    && rm -r /var/lib/apt/lists/*


# MOUNT A VOLUME WITH OUR DEFAULT CONTAINER CONFIG
# THIS CAN BE OVERWRITTEN BY MOUNTING TO THE /etc/container-config-overwrite FOLDER
COPY ./container-config /etc/container-config

ENV SERVER_NAME localhost
ENV SERVER_ADMIN root

ENV SSMTP_MAILHUB ""

ENV APACHE_RUN_HOME /var/www
ENV APACHE_DOCUMENT_ROOT /var/www/html
ENV APACHE_ERROR_LOG /dev/stdout
ENV APACHE_ACCESS_LOG /dev/stdout

# Copy main startup script
COPY container_start /etc/container_start
RUN chmod +x /etc/container_start
CMD ["/etc/container_start"]

COPY ./default-webroot/index.php /var/www/html/index.php

## Custom Webroot
#COPY default-webroot/ /var/www/html/
#
## Add custom php to conf.d dir
#COPY config/php-confd/* /usr/local/etc/php/conf.d/
#
## Add custom apache configurations
#COPY config/sites-enabled/* /etc/apache2/sites-enabled/
#COPY config/conf-enabled/* /etc/apache2/conf-enabled/
#
## Create directory for startup scripts
#RUN mkdir -p /etc/start_httpd.d
#COPY ../overrides-php-apache/startup-scripts /etc/start_httpd.d/

# HEALTHCHECK --interval=2m --timeout=5s CMD curl -f http://localhost/ || exit 1

# OVERRIDE APACHE DEFAULTS (www-data/www-data and /var/www)
#ENV APACHE_RUN_USER xxx
#ENV APACHE_RUN_GROUP xxx
# UNTESTED - to change document root
# RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
# RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
#ENV GIT_SSH_KEY_BASE64 ""
#ENV KNOWN_HOSTS_BASE64 ""
#ENV GIT_EMAIL ""
#ENV GIT_NAME ""
