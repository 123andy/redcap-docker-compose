# redcap-docker-compose

## Configuration
The services are mainly configured through a `.env` environment file.  Additional customization can be done my modifying
the files in the `override-*` directories.  Detailed examples should be added to this documentation - please help with
a pull request if you do something interesting.  

> Anytime you make a change, you must 'rebuild' the docker container(s)
 -- *stopping and restarting isn't sufficient*.  See the commands below for examples


## Getting Started
1. Install docker on your machine.
   * [Docker Community Edition](https://store.docker.com/search?type=edition&offering=community)
      * You might also consider installing a docker GUI such as [Kitematic](https://kitematic.com/)
1. Download or clone [andy123/redcap-docker-compose](https://github.com/123andy/redcap-docker-compose) to your computer.  
   * A zip file is available here: [zip download](https://github.com/123andy/redcap-docker-compose/archive/master.zip)
   * Unzip this into a good place on your computer (e.g. desktop or documents)
1. Download the full install version of REDCap from the community consortium.
   * [REDCap Community Download Page](https://community.projectredcap.org/page/download.html) - requires authentication.
     * Can't log in? Talk to your site's REDCap administrator if you need help getting the source code or if you need 
       to [apply for community access](https://community.projectredcap.org/articles/26/getting-started-introduction-learning-the-basics.html)
   * If you want the *setup assistant* to help you install REDCap, place the `redcapx.y.z.zip` file in the
   `REDCAP-DOWNLOADS` folder from the previous step and it will be auto-extracted
   and installed to your webroot.
1. Open the `.env` file in the redcap-docker-compose folder with a text editor or IDE.
   * Review the settings and see what you can easily customize 
     * You will want to select where your `WEBROOT_DIR` folder will reside on your computer.  This is where your redcap
       source code will go. You might want this in your Documents or Desktop folder for easy access.  The default will
       be inside the `VOLUMES/www` folder.
     * You will want to understand where the log files will be located.  Default is `VOLUMES/logs`
     * If you are on a MAC, you should set the `APACHE_RUN_USER_ID` setting to your current mac users' UID.  This can
       be found by opening your terminal and typing `id`.  Typically it is 501 for the first user on a mac.
1. Lastly, you will start-up the containers.  This can be done from the terminal/command line by navigating to the folder
   containing the `docker-compose.yml` file and running a docker-compose up command.

 
## Installing REDCap

There are two ways to get your new docker-compose REDCap environment running - I recommend using the startup assistant,
 but you can also choose the manual method if you want to learn more or have an existing environment you are porting
 over.

### A) Startup Assistant
There is an optional *startup assistant* that can help extract your first redcap install and configure your database.php
file.
1. Before you start up the containers the first time, download the `redcapx.x.x.zip` file from consortium into the
 `REDCAP-DOWNLOADS` directory.
1. Then goto the terminal where this file is located and run:
```bash
redcap-docker-compose$ docker-compose up
```
The first startup might take a while, so be patient.  Keep an eye on your `WEBROOT_DIR` and review the logs.  If all
goes well, you should be able to skip to the [Configure REDCap](#configure-redcap) section.

### B) Manual setup WEBROOT_DIR
Alternately, you can manually add the contents of a redcap installer into your `WEBROOT_DIR` folder and then setup the
`database.php` file with your connection information.

1. Fire up your containers with:
```bash
redcap-docker-compose$ docker-compose up
```
1. Watch the logs, if all went well, you should be able to get a phpinfo() page at [http://localhost](http://localhost)
1. Next, open your installer zip (or take your existing development folder) and place it into the `WEBROOT_DIR` folder
as configured in your `.env` file.  Be sure to read the note on the `/redcap/` folder after this section.
1. Next, we need to edit the `database.php` file in the webroot to match your *MYSQL_XXX* database parameters from the
 .env file.  Using your text editor, open `database.php` and insert something like:
   ```php
   <?php
     global $log_all_errors;
     $log_all_errors = FALSE;  // You could set this to true as well
     $hostname  = 'db';        // Note this is the 'internal' name (as seen by a docker-container)
     $db        = 'redcap';
     $username  = 'redcap';
     $password  = 'redcap123';
     $salt      = '12345678';
   ```

> #### What should I do with the `/redcap/` folder?
>
>When you place your redcap files in your `WEBROOT_DIR` you can either include the `/redcap/` folder or leave it out.
I prefer to leave off so the REDCap homepage will be [http://localhost](http://localhost).
>
>If you include it, your homepage will be [http://localhost/redcap](http://localhost/redcap). Please note that if you
 do leave the /redcap/ folder there, you will need to update the crontab root so the correct url is called every
 minute:  Change `wget -O /dev/null web/cron.php` to `wget -O /dev/null web/redcap/cron.php` and force a rebuild of 
 your cron container with `docker-compose up -d --no-deps --build cron`
>
>Note that if you use the *startup assistant* it leaves off the /redcap/ folder.


## Configure REDCap
At this point, we assume that you have a running set of containers.  If you should now setup your database.

1. Open the installer at [http://localhost/install.php](http://localhost/install.php)
    * You can *IGNORE* the part about creating a new database user - you already have one as defined in the MYSQL_XXX
variables in the `.env` file.
1. Copy the SQL to generate your redcap database and then execute it!  See [Connecting to the database](#connecting-to-the-database)


### Connecting to the database
There are at least three ways to connect to your database:
1. You can connect to the database from your client using any database tool (dataGrip, phpWorkbench, etc).  The default
 port is 3306 but you can change this in the `.env` file.
1. You have phpMyAdmin running inside this service - simply visit [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
1. You can connect to the database from the command line as illustrated in the example below:
```
$ docker-compose exec db mysql -u redcap -predcap123
redcap-docker-compose$ docker-compose exec db mysql -u redcap -predcap123
Welcome to the MySQL monitor.  Commands end with ; or \g.
Server version: 5.7.23-log MySQL Community Server (GPL)

Type 'help;' or '\h' for help. Type '\c' to clear the current input statement.

mysql> 
```


## Logging
* Some logs (like apache access and cron) are passed through to the docker runtime and can be viewed by calling 
`docker-compose logs` or can be viewed using a gui tool.  
* Other log files (like mysql slow queries and php_errors.log) are mapped through to a log volume on your computer for
easy monitoring using tools like notepad++ (pc), console (mac), or just `tail -f *` from the terminal.
* Custom application logs should be written to `/var/log/redcap` inside the web image that maps to the `$LOG_DIR` 
as configured in the `.env` file.
* Log rotation can be configured so your log files don't grow too large - see `override-cron/logrotate` for an example. 


## Usage
* You can access your webroot at [http://localhost](http://localhost/)
* You can access your mailhog at [http://localhost/mailhog/](http://localhost/mailhog/)
   * (don't forget the trailing slash)
* You can access your phpMyAdmin at [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
   * (don't forget the trailing slash)


## Useful Docker-compose Commands
Please note that **all commands** must be run from the root directory where the `docker-compose.yaml` file is 
located (unless you specify additional parameters).  

   * `docker-compose up -d` - this will run and detach form the containers leaving them to run in the background.  
   This is the most common way I run these containers.  You can view the status and logs from other `docker-compose`
   commands or from the GUI of docker-compose tools like [Kitematic](https://kitematic.com/)
   * `docker-compose up` - this will run the containers in the current window.  If you close your window the containers
     will be stopped.  I sometimes do this the first time.
   * `docker-compose up -d --force-recreate` - This should be run if you modify the .env file or other custom override
     files and need those changes to be incorporated into the containers -- otherwise your changes will not appear in the
     running images.
   * `docker-compose up -d --no-deps --build <CONTAINER_NAME>` - If you just want to rebuild one container and not all
     of them. Valid names are `web`, `db`, `cron`, `mailhog`, `phpmyadmin` and `startup`)
   * `docker-compose stop` - this will stop the docker process (which would be good to do if you want to save battery)
   * `docker-compose down` - this will stop **and remove** the containers - meaning the next time you call up they will be 
     recreated (this is similar to the --force-recreate tag)
   * `docker-compose down -v` - this will stop and remove the containers *along with their internal volumes*.  For
     example, if you call this any saved email messages from mailhub would be removed.
   * `docker ps` - shows you all running containers - see the docker command reference
   * `docker ps -a` - shows you all running *and stopped* containers.
   

## GIT/SSH Integration
There is an additional `.env-git-ssh` file that allows you to enable your web container to run git commands with
external git repos.  For example, you can have your container automatically download its web content from a
git repo when it is created.  You could also add a hook to your app that causes it to refresh its code whenever you
commit changes to your code.  This is great for building a continuous integration server for test purposes.

More detailed documentation is required.

### Other Misc Notes

#### Shutting down
You can shut down your servers by pressing ctrl-c in the window where you ran `docker-compose up`.  After a few seconds it should report all off.

```
^CGracefully stopping... (press Ctrl+C again to force)
Stopping redcap ... done
Stopping mailhog ... done
```

This stops your running containers but does not delete them.  They are still there on your machine and will be restarted when you run `docker-compose up` again.
Try restarting again with `docker-compose up -d` - it should be MUCH faster after the initial load.  Adding the `-d` means
detached so you can close your terminal window and the service will continue to run.


#### Logging into the server
To get a bash shell as root in the redcap server, you can run:
```
$ docker-compose exec web /bin/bash
root@5af71d765e77:/# 
```
Keep in mind any changes you make will be transitory and lost if you ever recreate the container

### How can I see what's running?
The command `docker ps` shows what containers are running.  If your server is up, they will appear here.
The command `docker ps -a` shows all contaiers regardless of run state.
