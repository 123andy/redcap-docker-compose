# REDCap Docker Compose Environment

![Docker Compose][docker-compose-logo]
![REDCap][redcap-logo]

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [About](#about)
- [Features](#features)
- [Quick-Start](#quick-start)
- [Full Documentation](#full-documentation)
- [Updates](#updates)
- [License](#license)
- [Contributing](#contributing)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

[![documentation-button](rdc/documentation/button_documentation.png)](rdc/documentation/README.md)


## About
This repo is designed to build a local development instance of REDCap on your laptop.  It also includes some aids 
to try and facilitate a rapid setup of REDCap using either your consortium credentials or a complete installer you 
receive from a teammate at your institution.

This is intended to be one of the fastest and easiest ways to create a local development instance of REDCap on your 
computer or test server.

This is not intended to be used as a production server, although we do run something pretty similar here at Stanford.
Should you want to run this as production, be sure to edit the `redcap.ini` file in the `override-web/php` folder
to have production-ready php settings.

## Features
 * Mailhog to capture outbound email for review
 * X-Debug support for detailed server-side inspection
 * PhpMyAdmin support for easy database maintenance / backups / restores
 * Easy to modify php-version or mysql-version and rebuild your environment in minutes

## Quick-Start
 * [Install Docker Community Edition](https://docs.docker.com/get-docker) (requires docker account which is free)
 * [Download this repository](https://github.com/123andy/redcap-docker-compose/archive/master.zip) and unzip it to your computer
 * Open your download directory using a good IDE (
 [phpStorm](https://www.jetbrains.com/phpstorm/), 
 [Visual Studio Code](https://code.visualstudio.com/),
 [Atom](https://atom.io/) - does not support xdebug, etc... )
 * Edit the `.env` file located in the `rdc` folder review the contents, making changes as necessary.
 * Once `.env` file settings are correct, from the `rdc` folder type `docker-compose up -d`
 * Open your web browser and goto `http://localhost` (or, in some cases with macs `http://127.0.0.1`) and follow
  directions for further installation
 
## Full Documentation
See the [detailed documentation](rdc/documentation/README.md) for more information!  Keep in mind this is a community
effort so feedback is appreciated.  Please create issues here with any suggestions or make a pull request with improvements.

## Updates
* 2019-10-03  Improved documentation and cleanup of unused settings (issue #4)
* 2019-09-06  Removed ssmtp and replaced with msmtp
* 2019-06-06  Add X-Debug configuration. 
* 2019-01-24  Changed folder layout and optimized unzipping after upload to be much faster
* 2018-08-19  Added .env file and added UID override for MAC users to maintain file ownership (see .env)
* 2018-08-04  Added support for auto-install from `redcapx.y.z.zip`
* 2018-08-01  Major refactoring into docker-compose 3
* 2020-09-24  Minor documentation cleanup and testing for Windows

## License
Copyright (c) 2016 Andrew Martin
Licensed under the MIT license.

## Contributing
Please make pull requests to extend the functionality and documentation

[redcap-logo]: rdc/documentation/redcap-logo-large.png "REDCap"
[docker-compose-logo]: rdc/documentation/docker-compose.png "Docker Compose"
