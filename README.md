# REDCap Docker Compose Environment

![Docker Compose][docker-compose-logo]
![REDCap][redcap-logo]

This repo is designed to build a local development instance of REDCap on your laptop.  It also includes some aids to try and facilitate a rapid setup of REDCap using either your consortium credentials or a complete installer you receive from a teammate at your institution.

This is intended to be one of the fastest and easiest ways to create a local development instance of REDCap on your computer or test server.

This is not intended to be used as a production server, although we do run something pretty similar here at Stanford.

## About
This docker-compose will build multiple servers as part of a docker group to host REDCap on your local computer/server.
It consists of:
 * The official PHP-Apache docker image (Currently version 7.2)
 * The official cd MySql docker image (currently version 5.7)
 * A basic alpine-based MailHop image (for capturing outbound emails from REDCap for your review)

(optional)
 * The official PhpMyAdmin web-based mysql tool for managing the database.
 * A basic alpine-based cron image (for running the REDCap cron and handling log rotation)
 * A basic alpine-based setup image to create your first REDCap webroot, database.php, populate the REDCap tables and configure REDCap.

The advantage of this docker-based method is you can easily upgrade database versions, php versions, and see how
these changes might affect your projects or custom code.

## Configuration
The services are mainly configured through a `.env` environment file or shell
environment variables.  Additional customization can be done by modifying the
files in the `override-*` directories.

See the [documentation](documentation/README.md) for more information on getting started!

## Updates
* 2019-01-24  Changed folder layout and optimized unzipping after upload
* 2018-08-19  Added .env file and added UID override for MAC users to maintain file ownership (see .env)
* 2018-08-04  Added support for auto-install from `redcapx.y.z.zip`
* 2018-08-01  Major refactoring into docker-compose 3

## License
Copyright (c) 2016 Andrew Martin
Licensed under the MIT license.

## Contributing
Please make pull requests to extend the functionality and documentation

[redcap-logo]: documentation/redcap-logo-large.png "REDCap"
[docker-compose-logo]: documentation/docker-compose.png "Docker Compose"