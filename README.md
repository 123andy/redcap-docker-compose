# REDCap Docker Compose Environment

![Docker Compose][docker-compose-logo]
![REDCap][redcap-logo]

## About
This repo is designed to build a local development instance of REDCap on your laptop.  It also includes some aids 
to try and facilitate a rapid setup of REDCap using either your consortium credentials or a complete installer you 
receive from a teammate at your institution.

This is intended to be one of the fastest and easiest ways to create a local development instance of REDCap on your 
computer or test server.

This is not intended to be used as a production server, although we do run something pretty similar here at Stanford.

## Quick-Start
 * Download this repository and unzip it to your computer
 * Have Docker installed (see [documentation](rdc/documentation/README.md) for more details)
 * Open a terminal, goto the `rdc` folder review the contents of the `.env` file.
 * Once `.env` file settings are correct, type `docker-compose up -d`
 * Open your web browser and goto `http://localhost` and follow directions
 
## Details
This docker-compose will build multiple servers as part of a docker group to host REDCap on your local computer/server.
It consists of:
 * The official PHP-Apache docker image (Currently version 7.2)
 * The official cd MySql docker image (currently version 5.7)
 * A basic alpine-based MailHop image (for capturing outbound emails from REDCap for your review)
 * A basic alpine-based cron image (for running the REDCap cron and handling log rotation)

(optional)
 * The official PhpMyAdmin web-based mysql tool for managing the database.  This is commented out by default.  If you want to include it, edit the `docker-compose.yml` file and uncomment out the phpmyadmin section.

The advantage of this docker-based method is you can easily upgrade database versions, php versions, and see how these changes might affect your projects or custom code.

## Configuration
The services are mainly configured through a `.env` environment file or shell
environment variables.  Additional customization can be done by modifying the
files in the `override-*` directories.

See the [documentation](rdc/documentation/README.md) for more information on getting started!

## X-Debug Configuration (optional for PHPStorm)
X-Debug allows you to insert breakpoints in your php code and evaluate variables from the server in your IDE.  Directions
for using PhpStorm are provided here.  Basically, you configure an xdebug server on PhpStorm.
1. After your docker server is up and running, open your project folder in PhpStorm (e.g. `~/redcap/`)
1. In PhpStorm, goto `preferences -> Languages & Frameworks -> PHP -> Server` and create new server.
   1. Name the server 'localhost-xdebug-server' - this name matches 
   1. Set the hostname to be 'localhost' and leave the port at '80'
   1. Check the "Use Path Mapping" option and find the redcap-docker-compose file.
      * On the left side, set the path of your codebase (e.g. `~/redcap/www`)
      * On the right side under `Absolute path on the server` enter in the value `/var/www/html`
1. After creating the server on PHPStorm go to `Run -> Edit COnfiguration`.  Create new "PHP Remote Debug" configuration.
   1. Make sure to check `Filter debug connection by IDE Key`. 
   1. Then select `localhost-xdebug-server` for server and type `PHPSTORM` for IDE Key.
   1. You can validate your configuration by clicking on Validate under Pre-Configuration. 

1. Finally you need to install the PhpDebug Browser debugger extension from 
   [https://www.jetbrains.com/help/phpstorm/browser-debugging-extensions.html]


## Updates
* 2019-06-06  Add X-Debug configuration. 
* 2019-01-24  Changed folder layout and optimized unzipping after upload to be much faster
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
