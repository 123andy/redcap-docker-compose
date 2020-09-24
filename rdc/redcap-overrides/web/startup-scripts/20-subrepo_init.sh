#!/usr/bin/env bash

# This script enables gitsubrepo - a git module that helps to manage multiple embedded git repos.  I'm deactivating it by default and you can enable it if you like
exit 0;

# Give www-data user a shell (/var/www)
usermod -s /bin/bash $APACHE_RUN_USER
HOME_DIR=`getent passwd $APACHE_RUN_USER | cut -d: -f6`

chown $APACHE_RUN_USER:$APACHE_RUN_GROUP $HOME_DIR


### SETUP USER HOME AND WEBROOT DIRS ###
SSH_DIR=$HOME_DIR/.ssh

if [ -z "$APACHE_DOCUMENT_ROOT" ]; then
  WEBROOT_DIR=/var/www/html
else
  WEBROOT_DIR=$HOME_DIR/html
fi
mkdir -p -m 0700 $SSH_DIR
echo "Apache user is $APACHE_RUN_USER:$APACHE_RUN_GROUP with home $WEBROOT_DIR"

# Set up SSH and set the id_rsa if specified as a base64 encoded string
if [ ! -z "$GIT_SSH_KEY_BASE64" ]; then
 echo $GIT_SSH_KEY_BASE64 | base64 -d > $SSH_DIR/id_rsa
 chmod 600 $SSH_DIR/id_rsa
 echo "Seeded ssh key"
else
 echo "No GIT_SSH_KEY_BASE64 Present"
fi

# Disable Strict Host checking for non interactive git clones
echo -e "Host *\n\tStrictHostKeyChecking no\n\tForwardAgent yes\n\tIdentityFile ${SSH_DIR}/id_rsa\n" >> $SSH_DIR/config

# Add known hosts
if [ ! -z "$KNOWN_HOSTS_BASE64" ]; then
 echo $KNOWN_HOSTS_BASE64 | base64 -d > $SSH_DIR/known_hosts
 echo "Seeded known hosts file"
fi

# Fix permissions for the user's root directory
chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $SSH_DIR


# Setup git variables
if [ ! -z "$GIT_EMAIL" ]; then
 /sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "git config --global user.email ""$GIT_EMAIL"""
else
 echo "No GIT_EMAIL specified"
fi
if [ ! -z "$GIT_NAME" ]; then
 /sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "git config --global user.name ""$GIT_NAME"""
else
 echo "No GIT_NAME specified"
fi
/sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "git config --global push.default simple"
/sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "git config --global core.pager cat"


# Setup git subrepo
git clone https://github.com/ingydotnet/git-subrepo.git /usr/bin/git-subrepo && \
    cd /usr/bin/git-subrepo && \
    git checkout release/0.4.0
echo -e "\nsource /usr/bin/git-subrepo/.rc\n" >> $HOME_DIR/.bash_profile



# Setup webroot repo
if [ ! -z "$WEBROOT_REPO_SSH_URL" ]; then
 # dont pull code down if a .git folder exists
 if [ ! -d "$WEBROOT_DIR/.git" ]; then
   echo "NO GIT in $WEBROOT_DIR"
   # Pull down code from git for our site!
   # Remove the test index file
   cd $WEBROOT_DIR
   rm -f index.*
   if [ ! -z "$WEBROOT_REPO_BRANCH" ]; then
     /sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "cd $WEBROOT_DIR; git clone -b $WEBROOT_REPO_BRANCH $WEBROOT_REPO_SSH_URL $WEBROOT_DIR"
   else
    echo "git clone $WEBROOT_REPO_SSH_URL $WEBROOT_DIR/"
    /sbin/runuser $APACHE_RUN_USER -s /bin/bash -c "cd $WEBROOT_DIR; git clone $WEBROOT_REPO_SSH_URL $WEBROOT_DIR"
   fi
 fi
fi


# Set up web repo folders for each env variable
if [ ! -z "$WEBROOT_REPO_FOLDERS" ]; then
    cd $HOME_DIR;
    IFS=','
    for i in $WEBROOT_REPO_FOLDERS; do
        echo $i
        mkdir -p $i
        chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $i
    done

    # In case additional volumes are mounted to the HOME_DIR (var/www), let's double-check permissions
    for f in */; do
        if [[ -d $f ]]; then
            echo "$f"
            chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $f
        fi
    done
fi