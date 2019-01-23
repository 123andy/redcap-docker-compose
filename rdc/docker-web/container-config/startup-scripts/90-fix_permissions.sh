#!/usr/bin/env bash

# ON NEW INSTALL CHECK PERMISSIONS
permissionFile="/var/www/html/.delete_to_recheck_permissions.txt";
if [[ ! -f "${permissionFile}" ]]; then
    echo "Verifying permissions on web user homedir ${APACHE_RUN_HOME}"
    nohup chown -R $APACHE_RUN_USER:$APACHE_RUN_GROUP $APACHE_RUN_HOME
    echo "Delete this file and recreate the web container to verify the file permissions of this directory" > $permissionFile
else
    echo "To force permission verification, delete ${permissionFile} and re-run."
fi