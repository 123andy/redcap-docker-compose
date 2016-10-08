<?php
/** A quick and dirty startup helper for REDCap **/


// START PAGE
?>
<html>
    <head>
        <meta http-equiv="refresh" content="3">
    </head>
    <body>
        <h1>Hello REDCapper</h1>
<?php

$redcap_ready = file_exists("redcap/install.php");
if ($redcap_ready) {
    print "<div>Looks like you've got redcap where it should be - <a href='redcap/install.php'>launch the installer</a> and continue...</div>
    
        <div>To access mysql on your docker instance, check out the directions here: 
    
    ";
} else {
    print "<h3>Next steps:</h3>
           <ol>
                <li>Visit <a href='https://community.projectredcap.org/page/download.html' target='_blank'>The REDCap download site</a></li>
                <li>Unpack the zip file such that your folder hierarchy is similar to <code>redcap-docker-compose/www/redcap/install.php</code></li>
           </ol>
    ";
}




// END PAGE
?>
        <h6>This page will refresh every 3 seconds...</h6>
    </body>
</html>