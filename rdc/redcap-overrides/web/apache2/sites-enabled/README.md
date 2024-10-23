<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [How To](#how-to)
  - [The Default Setup](#the-default-setup)
  - [Replacing the default vhost](#replacing-the-default-vhost)
  - [redcap.conf.example](#redcapconfexample)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

# How To
You have many ways to customize this web server.  

## The Default Setup
The default `000-default.site` is enabled if you don't do anything else.
It is a full wildcard vhost site on port 80, e.g.
```
<VirtualHost *:80>
        ServerName ${SERVER_NAME}
        ServerAlias ${SERVER_ALIAS}
        ServerAdmin ${SERVER_ADMIN}
        DocumentRoot ${APACHE_DOCUMENT_ROOT}
        ErrorLog ${APACHE_ERROR_LOG}
        CustomLog ${APACHE_ACCESS_LOG} combined
        IncludeOptional /etc/apache2/sites-available/site.custom
</VirtualHost>
```

Additionally, it includes a file called `/sites-available/site.custom`
which can be used to place in modifications to your apache.conf.  For example,
the REDCap setup changes the directory index with:
```
DirectoryIndex index.php index.html redcap-setup.php
```

## Replacing the default vhost
You could override the `000-default.conf` site in the `sites-enabled` folder, or, better,
you can add a new `mysite.conf` to the folder and then disable the default conf by setting
an enviroment variable to true in the `.env`

`REMOVE_DEFAULT_VHOST=true`

## redcap.conf.example
If you want to use the redcap.conf.example file, you would:
1. replace it with a file ending in `.conf`
2. set `REMOVE_DEFAULT_VHOST=true`
3. Run `docker compose down; docker compose up -d` / because these changes are only to the compose add-ins, you don't need to rebuild the images.
