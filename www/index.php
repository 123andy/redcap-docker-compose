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
        <hr>
<?php

$redcap_ready = file_exists("redcap/install.php");
if ($redcap_ready) {
    print "<p>Looks like you've got redcap where it should be - <a href='redcap/install.php'>start the installer</a> and continue...</p>
    
        <p>To access mysql on your docker instance, check out the directions here: <a href='https://github.com/123andy/redcap-docker-compose#connecting-to-the-database' target='_blank'>mysql launch in docker</a></p>
    
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
        <br>
        <p>Good luck!</p>
        <p> -Andy</p>
        <h6>This page will refresh every 3 seconds...</h6>
    </body>
</html>