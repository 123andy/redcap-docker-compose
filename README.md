# redcap-docker-compose

A docker-compose script for building REDCap with Mailhog.  This is the easiest way to create a local development instance of REDCap on your computer.

## Configuration Steps
1. Install docker on your machine.  https://docs.docker.com/#components
2. Clone this repository or simply download the docker-compose.yml file.
3. Open your command shell or terminal and run `docker-compose up` from the directory with the docker-compose.yml file.  The first time you run this it make take a few minutes as all of the image files will be pulled down and cached on your computer.
```
$ ls -latr
-rw-r--r--   1 andy123  staff   436 Oct  8 08:14 docker-compose.yml
drwxr-xr-x   3 andy123  staff   102 Oct  8 08:29 www
-rw-r--r--   1 andy123  staff  1381 Oct  8 08:32 README.md
-rw-r--r--   1 andy123  staff  1068 Oct  8 08:14 LICENSE

$ docker-compose up
Creating network "redcapdockercompose_default" with the default driver
Pulling mailhog (mailhog/mailhog:latest)...
latest: Pulling from mailhog/mailhog
4d06f2521e4f: Pull complete
...
Pulling redcap (andy123/redcap-docker:latest)...
latest: Pulling from andy123/redcap-docker
862a3e9af0ae: Pull complete
...
```
Next, compose will run the containers:
```
Creating mailhog
Creating redcap
Attaching to mailhog, redcap
mailhog    | 2016/10/08 15:37:50 Using in-memory storage
mailhog    | 2016/10/08 15:37:50 [SMTP] Binding to address: 0.0.0.0:1025
mailhog    | 2016/10/08 15:37:50 Serving under http://0.0.0.0:8025/
redcap     | => An empty or uninitialized MySQL volume is detected in /var/lib/mysql
redcap     | => Installing MySQL ...
mailhog    | [HTTP] Binding to address: 0.0.0.0:8025
mailhog    | Creating API v1 with WebPath: 
mailhog    | Creating API v2 with WebPath: 
redcap     | => Done!
redcap     | => Waiting for confirmation of MySQL service startup
redcap     | => Creating MySQL admin user with preset password
redcap     | => Done!
redcap     | ========================================================================
redcap     | You can now connect to this MySQL Server using:
redcap     | 
redcap     |     mysql -uadmin -predcap -h<host> -P<port>
redcap     | 
redcap     | Please remember to change the above password as soon as possible!
redcap     | MySQL user 'root' has no password but only allows local connections
redcap     | ========================================================================
redcap     | => Setting timezone
redcap     | => Setting max_input_vars
redcap     | => Setting php sendmail to ssmtp
redcap     | => Setting ssmtp link to mailhog
redcap     | => Configuring CRON
redcap     | no crontab for root
redcap     | => Starting Supervisor
```
4. Open another terminal window or your finder/windows explorer and you should see a few new folders have been created:
```
docker-redcap-compose
  - docker-compose.yml
  - www                 // Contains your web-root directory
  - mysql               // Contains your database
  - logs                // Contains log files (useful for debugging)
```
5. Open your web browser and goto http://localhost
  * you should see the contents of the index file in your www directory. 
6. Open http://localhost:81
  * You should see the web interface for MailHog.
  * Click 'OK' to allow notifications - they will come in handy when you get an email

  
## Installing REDCap

Now that you have a local LAMP server, you need to complete the installation of REDCap.  This means:

1. Goto the Consortium website and download the latest version
2. Unpack and move the contents of the redcapx.xx.x.zip folder into the `www` folder
3. Open your browser and goto http://localhost/redcap/install.php and follow the directions.

  
  
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


