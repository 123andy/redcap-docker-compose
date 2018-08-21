#!/usr/bin/env sh

set -e

PREFIX="/redcap_download"
TEMP="/tmp"
WEBROOT="/webroot"
extWEBROOT="${WEBROOT_DIR}"

set_redcap_config() {
    DATABASE_HOSTNAME=$1
    DATABASE_USER=$2
    DATABASE_PASSWORD=$3
    DATABASE_NAME=$4
    info_text=$5
    field_name=$6
    value=$7
    echo "set_redcap_config: $info_text"

    CONNECTION="-h $DATABASE_HOSTNAME -u$DATABASE_USER -p$DATABASE_PASSWORD $DATABASE_NAME"
    mysql $CONNECTION -e "UPDATE $DATABASE_NAME.redcap_config SET value = '$value' WHERE field_name = '$field_name';"
}

create_redcap_tables() {
    REQUIRED_PARAMETER_COUNT=6
    if [ $# != $REQUIRED_PARAMETER_COUNT ]; then
        echo "${FUNCNAME[0]} Creates a MySQL database, a DB user with access to the DB, and sets user's password."
        echo "${FUNCNAME[0]} requires these $REQUIRED_PARAMETER_COUNT parameters in this order:"
        echo "DATABASE_HOSTNAME    Database host that houses the redcap DB"
        echo "DATABASE_USER        Database user who will have access to DATABASE_NAME"
        echo "DATABASE_PASSWORD    Password of DATABASE_USER"
        echo "DATABASE_NAME        Name of the database to create"
        echo "DEPLOY_DIR           The directory where the app is deployed"
        echo "VERSION              The version of the schema files to be loaded"
        return 1
    else
        DATABASE_HOSTNAME=$1
        DATABASE_USER=$2
        DATABASE_PASSWORD=$3
        DATABASE_NAME=$4
        DEPLOY_DIR=$5
        VERSION=$6
    fi


    echo "Creating REDCap tables..."
    SQL_DIR=$DEPLOY_DIR/redcap_v$VERSION/Resources/sql
    CONNECTION_PARMS="-h $DATABASE_HOSTNAME -u$DATABASE_USER -p$DATABASE_PASSWORD $DATABASE_NAME"
    mysql $CONNECTION_PARMS < $SQL_DIR/install.sql
    mysql $CONNECTION_PARMS < $SQL_DIR/install_data.sql
    CONNECTION_VARS="$DATABASE_HOSTNAME $DATABASE_USER $DATABASE_PASSWORD $DATABASE_NAME"
    set_redcap_config $CONNECTION_VARS "Setting redcap_version..." redcap_version $VERSION

    files=$(ls -v $SQL_DIR/create_demo_db*.sql)
    for i in $files; do
        echo "Executing sql file $i"
        mysql $CONNECTION_PARMS < $i
    done
    echo "REDCap tables created"
}


if [[ "$PARSE_ZIP_INSTALLER" = true ]] || [[ "$FORCE_RUN" = true ]]; then

    if [ -z "$(ls -A ${PREFIX})" ]; then
        echo "REDCap Download is empty.  Download a zip installer and place it here for auto-creation of your web container"
    else
        # GET LATEST VERSION OF REDCAP
        version=$(find $PREFIX/redcap*.zip | sed -E 's/(.*redcap)([0-9]+)\.([0-9]+)\.([0-9]+)(\.zip)/\2.\3.\4/' | sort -t. -n -r | head -1)

        if [ -z "$version" ]; then
            echo "No valid REDCap.zip file (redcapx.y.z.zip) found"
        else
            # Save file to variable
            file="${PREFIX}/redcap${version}.zip"

            # Also make a public variable to show in log messages
            extFile="${REDCAP_DOWNLOAD_DIR}/redcap${version}.zip"

            echo "Latest REDCap version in $extFile is $version"

            # SEE IF WEBROOT IS PROVISIONED
            version_folder="redcap_v${version}"
            if [[ -f "${WEBROOT}/${version_folder}" ]]; then
                echo "Webroot (${extWEBROOT}) already has v${version}"
            else
                echo "Webroot (${extWEBROOT}) doesn't yet have v${version}"

                # SET TEMP WORKING AREA
                #dir=${file%.zip}
                dir="${TEMP}/redcap${version}"

                # COPY FILES FROM SOURCE TO DEST
                dbFile="${WEBROOT}/database.php"
                dbBackupFile="${WEBROOT}/database_${version}.php"
                if [[ -f "${dbBackupFile}" ]]; then
                    # SCRIPT HAS ALREADY RUN
                    echo "Database already auto-generated.  Delete ${extWEBROOT}/database_${version}.php and re-run to generate a new database.php"
                    echo "Startup has already run."
                else
                    # SEE IF IT HAS ALREADY BEEN UNZIPPED
                    if [[ -d "$dir" ]]; then
                        echo "Unzipped directory present at $dir"
                    else
                        echo "Unzipping archive to $dir (this could take a minute)..."
                        unzip -q $file -d $dir
                    fi

                    # LOOK FOR SOURCE FILES TO MOVE OVER
                    if [[ ! -d "$dir/redcap" ]]; then
                        echo "Unable to add ${version_folder} as $dir/redcap is missing.  Recreate the container and tru again."
                    else
                        echo "Copying redcap files from ${dir}/redcap/ to ${extWEBROOT} (this could take a minute)..."
                        cp -rf $dir/redcap/* $WEBROOT

                        # REBUILD DATABASE.PHP FILE
                        if [[ ! -f "${dbFile}" ]]; then
                            echo "Unable to find database.php file"
                        else
                            if [[ -f "${dbBackupFile}" ]]; then
                                echo "Database already auto-generated.  Delete ${extWEBROOT}/database_${version}.php and re-run to generate a new database.php"
                            else
                                echo "Backing up the default database.php"
                                mv -f $dbFile $dbBackupFile

                                echo "<?php"                                     > $dbFile
                                echo "  global \$log_all_errors;"              >> $dbFile
                                echo "  \$log_all_errors = FALSE;"             >> $dbFile
                                echo "  \$hostname  = 'db';"                   >> $dbFile
                                echo "  \$db  = '${MYSQL_DATABASE}';"          >> $dbFile
                                echo "  \$username  = '${MYSQL_USER}';"        >> $dbFile
                                echo "  \$password  = '${MYSQL_PASSWORD}';"    >> $dbFile
                                echo "  \$salt  = '12345678';"                 >> $dbFile

                                echo "${extWEBROOT}/database.php generated"

                                echo "REDCap v${version} installed to ${extWEBROOT} and database.php file generated" > $file.log
                            fi
                            # populate database
                            MYSQL_HOSTNAME=db
                            MYSQL_CONNECTION_PARMS="${MYSQL_HOSTNAME} ${MYSQL_USER} ${MYSQL_PASSWORD} ${MYSQL_DATABASE}"
                            create_redcap_tables ${MYSQL_CONNECTION_PARMS} ${WEBROOT} ${version}
                            # configure REDCAP
                            set_redcap_config ${MYSQL_CONNECTION_PARMS} "Setting redcap_base_url..." redcap_base_url "http://localhost/"
                        fi

                        echo "Cleaning up ${dir}"
                        rm -rf $dir
                    fi
                fi
            fi
        fi
    fi
else
    echo "To recreate your webroot, place the zip installer in ${REDCAP_DOWNLOAD_DIR} and call with the env file PARSE_ZIP_INSTALLER=true"
fi