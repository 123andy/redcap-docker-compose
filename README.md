# redcap-docker-compose

A docker-compose script builds a working php-mysql environment designed for REDCap.
  This is one of the easiest ways to create a local development instance of REDCap on your computer.

## Updates

2018-08-03  Major refactoring into docker-compose 3

## About
This docker-compose will build multiple servers as part of a docker group to host REDCap on your local computer/server.
It consists of:
 * The official PHP-Apache docker image (Currently version 7.2)
 * The official MySql docker image (currently version 5.7)
 * The official PhpMyAdmin web-based mysql tool for managing the database.
 * A basic alpine-based cron image (for running the REDCap cron and handling log rotation)
 * A basic alpine-based MailHop image (for capturing outbound emails from REDCap for your review)

## Configuration
The image is mainly configured through a .env environment file.  Additional customization can be done my modifying a
few files in the directory - details to be added.


## Getting Started
1. Install docker on your machine.
   * [Docker Community Edition](https://store.docker.com/search?type=edition&offering=community)
        * Optionally, you can also download a docker GUI such as [Kitematic](https://kitematic.com/)
1. Clone or download [this repository](https://github.com/123andy/redcap-docker-compose) to your computer.
   * [zip download](https://github.com/123andy/redcap-docker-compose/archive/master.zip)
1. Download the full install version of REDCap from the community consortium
   * [REDCap Community Download Page](https://community.projectredcap.org/page/download.html) - requires authentication.
   * Talk to your site's REDCap administrator if you need help getting the source code or if you need 
   to [apply for community access](https://community.projectredcap.org/articles/26/getting-started-introduction-learning-the-basics.html)
1. Open the `.env` file in the root folder where you downloaded this repo with your text editor or development tool.
   * Review the settings and see what you can easily customize 
     * You will want to select where your webroot folder will reside on your computer.  This is where your redcap source code will go.
     You migth want this in your Documents or Desktop folder for easy access.
     * You will want to understand where the log files will be located.
1. Lastly, you will start-up the containers.  This can be done from the terminal/command line by navigating to the folder
   containing the `docker-compose.yml` file and running the following commands:
   * `docker-compose up -d` - this will run and detach form the containers leaving them to run in the background.  
   This is the most common way I run these containers.  You can view the status and logs from other `docker-compose`
   commands or from the GUI of docker-compose tools like [Kitematic](https://kitematic.com/)
   * `docker-compose up` - this will run the containers in the current window.  If you close your window the containers
    will be stopped.
   * `docker-compose up -d --force-recreate` - This should be run if you modify the .env file or other custom override
    files and need those changes to be incorporated into the containers -- otherwise your changes will not appear in the
    running images.
   * `docker-compose stop` - this will stop the docker process (which would be good to do if you want to save battery)
   * `docker-compose down` - this will stop and remove the containers - meaning the next time you call up they will be 
   recreated (this is similar to the --force-recreate tag)
   * `docker-compose down -v` - this will stop and remove the containers *along with their internal volumes*.  For
   example, if you call this any saved email messages from mailhub would be removed.
   
   Please note that **all commands** must be run from the root directory where the `docker-compose.yaml` file is located.  

## Logging
Some logs are passed through to the docker runtime and appear by calling `docker-compose logs` or can be viewed using
a gui tool.  Other logs are mapped through to a volume.

 
## Installing REDCap

Once your container is up and running, you should be able to connect by opening [http://localhost](http://localhost)

You will likely have a phpinfo page if all has gone well.  The next step is to add and configure your REDCap webapp
and your REDCap database.

1. Unzip the `redcapx.y.z` folder.  Inside is a folder called just `redcap`.  
1. You want to take the **CONTENTS** of the redcap folder and place them inside your webroot 
   (as defined with the `WEBROOT_DIR` in the .env file)
   * If you configure redcap this way, `http://localhost` will by your homepage.
   * If, on the other hand, you prefer to have `http://localhost/redcap/` be your homepage, you would copy not only 
   the contents but the entire redcap folder to your webroot.  
     * Please note that if you do this, you need to update the crontab root file and change 
   `wget -O /dev/null web/cron.php` to `wget -O /dev/null web/redcap/cron.php` and force a rebuild of your containers.
1. Next, we need to edit the `database.php` file in the webroot to match your database parameters from the .env file.
   Using your text editor, open `database.php` and insert something like:
```php
<?php
  global $log_all_errors;
  $log_all_errors = FALSE;
  $hostname  = 'db';        // Note this is the 'internal' name (as seen by a docker-container)
  $db        = 'redcap';
  $username  = 'redcap';
  $password  = 'redcap123';
  $salt      = '12345678';
```


  
  
## Other Misc Notes

### Connecting to the database

I haven't finished getting the php-my-admin configured so for now you will have to connect to the database using an outside sql tool (mySql Workbench) or you could run mysql as follows from the bash shell:
Open a terminal or command-line shell on your computer and run:
```
$ docker exec -it redcap mysql -u admin -predcap
Welcome to the MySQL monitor.  Commands end with ; or \g.
Your MySQL connection id is 2
Server version: 5.5.52-0ubuntu0.14.04.1 (Ubuntu)

Copyright (c) 2000, 2016, Oracle and/or its affiliates. All rights reserved.

Oracle is a registered trademark of Oracle Corporation and/or its
affiliates. Other names may be trademarks of their respective
owners.

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql> 
```

### Where is this REDCap Image coming from?

The underlying git repo for the redcap-docker is here: https://github.com/123andy/redcap-docker
This image is built and stored in dockerhub as: https://hub.docker.com/r/andy123/redcap-docker/

If you wish to make additional improvements, please clone the git repo and build the docker redcap image locally.

  
### Shutting down
You can shut down your servers by pressing ctrl-c in the window where you ran `docker-compose up`.  After a few seconds it should report all off.

```
^CGracefully stopping... (press Ctrl+C again to force)
Stopping redcap ... done
Stopping mailhog ... done
```

This stops your running containers but does not delete them.  They are still there on your machine and will be restarted when you run `docker-compose up` again.

Try restarting again with `docker-compose up` - it should be MUCH faster this time.


### Logging into the server
To get a bash shell as root in the redcap server, you can run:
```
$ docker exec -it redcap bash
root@5af71d765e77:/# 
```
Keep in mind any changes you make will be transitory and lost if you ever run docker-compose down.

### How can I see what's running?
The command `docker ps` shows what containers are running.  If your server is up, they will appear here.
The command `docker ps -a` shows all contaiers regardless of run state.

### How can I remove everything?
Run `docker-compose down` from the directory with the docker-compose.yml file.  This 'tears down' the networks and containers.
However, the image files you downloaded in the beginning are still on your machine.  To remove them use `docker rmi`:
```
$ docker images
REPOSITORY              TAG                 IMAGE ID            CREATED             SIZE
andy123/redcap-docker   latest              1b094a98c9e0        12 hours ago        464.1 MB
mailhog/mailhog         latest              2a8991b34c59        4 weeks ago         46.56 MB
$ docker rmi mailhog/mailhog andy123/redcap-docker
```
This will free up all of the disk space on your machine.


