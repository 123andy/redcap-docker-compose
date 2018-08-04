#!/usr/bin/env sh

set -e

PREFIX="/redcap_download"
TEMP="/tmp"
WEBROOT="/webroot"
extWEBROOT="${WEBROOT_DIR}"

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
                                echo "\\tglobal \$log_all_errors;"              >> $dbFile
                                echo "\\t\$log_all_errors = FALSE;"             >> $dbFile
                                echo "\\t\$hostname  = 'db';"                   >> $dbFile
                                echo "\\t\$db  = '${MYSQL_DATABASE}';"          >> $dbFile
                                echo "\\t\$username  = '${MYSQL_USER}';"        >> $dbFile
                                echo "\\t\$password  = '${MYSQL_PASSWORD}';"    >> $dbFile
                                echo "\\t\$salt  = '12345678';"                 >> $dbFile

                                echo "${extWEBROOT}/database.php generated"

                                echo "REDCap v${version} installed to ${extWEBROOT} and database.php file generated" > $file.log
                            fi
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