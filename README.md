# redcap-docker-compose

![Docker Compose][docker-compose-logo]
![REDCap][redcap-logo] 

This docker-compose script builds a working php-mysql environment designed for REDCap.
  This is one of the easiest ways to create a local development instance of REDCap on your computer or test server

## About
This docker-compose will build multiple servers as part of a docker group to host REDCap on your local computer/server.
It consists of:
 * The official PHP-Apache docker image (Currently version 7.2)
 * The official MySql docker image (currently version 5.7)
 * The official PhpMyAdmin web-based mysql tool for managing the database.
 * A basic alpine-based cron image (for running the REDCap cron and handling log rotation)
 * A basic alpine-based MailHop image (for capturing outbound emails from REDCap for your review)
 * A basic alpine-based setup image to create your first REDCap webroot and database.php

The advantage of this docker-based method is you can easily upgrade database versions, php versions, and see how
these changes might affect your projects or custom code

## Configuration
The services are mainly configured through a `.env` environment file.  Additional customization can be done my modifying
the files in the `override-*` directories.
  
See the [documentaton](documentation/README.md) for more information on getting started!

## Updates
* 2018-08-04  Added support for auto-install from `redcapx.y.z.zip`
* 2018-08-01  Major refactoring into docker-compose 3

## License
Copyright (c) 2016 Andrew Martin  
Licensed under the MIT license.

## Contributing
Please make pull requests to extent the functionality and documenatation

[redcap-logo]: documentation/redcap-logo-large.png "REDCap"
[docker-compose-logo]: documentation/docker-compose.png "Docker Compose"