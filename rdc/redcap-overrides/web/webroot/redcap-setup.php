<?php

/**
 * This is the REDCap Installer Page
 * It helps you upload and install REDCap on a clean web server
 */

set_time_limit ( 1200 ); // 20 Minute Execution Time

spl_autoload_register(function ($class_name) {
    include $class_name . '.php';
});

$RI = new REDCapInstaller();
$RI->displayPageHeader();

?>
    <div id="main-navbar" class="navbar navbar-dark bg-dark navbar-fixed-top">
        <a class="navbar-brand" href="/">
            <img style="height: 75px;" src="https://www.ctsi.ufl.edu/files/2017/07/REDCap-App-Icon.png">
        </a>
        <h1 class="text-light">REDCap Docker-Compose Installer</h1>
        <small class="text-light">By <a class="text-light" href="mailto:andy123@stanford.edu">Andy Martin</a></small>
    </div>

    <div class="container">
<?php

$RI->displayAlerts($RI->successes, "alert-success");
$RI->displayAlerts($RI->errors);

if ($RI->step == 1) {
    // STARTING STEP
    ?>
    <form id="form-upload" enctype="multipart/form-data" class="form" method="POST">
        <div class="mt-2 card install">
            <div class="card-header bg-cardinal text-light">
                <h3><i class="fas fa-arrow-up"></i> REDCap Installation Options</h3>
            </div>
            <div class="card-body">
                <div>
                    <h5>
                        To install REDCap, we need the full zip installer.  There are two ways to get this installer
                        file to this script:
                    </h5>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="consortium" class="form-check-input" name="dl-option">Use your
                            <a target="_blank" href="https://community.projectredcap.org">Community Consortium</a> login
                            to download your favorite REDCap version automatically
                        </label>
                    </div>
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="radio" value="upload" class="form-check-input" name="dl-option">Use a local copy
                            of the full zip installer, perhaps provided by a colleague or
                            consortium representative <i>(typically named redcapx.y.z.zip)</i>
                        </label>
                    </div>
                    <div class="mt-4">
                        <small class="text-center">
                            If you don't have either of these, then you need to reach out to your institution's REDCap
                            Consortium representatives for assistance.  This can be found at our
                            <a target="_blank" href="https://project-redcap.org/partners/">Consortium Site</a>
                        </small>
                    </div>
                </div>

                <div class="install-option option-consortium mt-2 p-2 border border-secondary rounded">
                    <h5>Enter your REDCap Consortium Credentials:</h5>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" class="form-control" name="username"
                               placeholder="Consortium username (e.g. andy.martin)">
                        <small id="username-help" class="form-text text-muted">Look above your profile image for your
                            username, typically it is firstname.lastname
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Password">
                    </div>
                    <div class="form-group">
                        <label for="password"><h6>Select REDCap Version</h6></label>
                        <div>
                            <select name="version"><?php echo $RI->getInstallerVersions() ?></select>
                        </div>
                        <small class="form-text text-muted">Select the version of REDCap to install</small>
                    </div>
                </div>

                <div class="install-option option-upload mt-2 p-2 border border-secondary rounded">
                    <h5>Select the path to your <code>redcap_vx.y.z.zip</code> full-installer file:</h5>
                    <div class="input-group mb-3">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="installer-upload" name="installer-upload">
                            <label class="custom-file-label" for="installer-upload">Choose file</label>
                        </div>
                    </div>
                </div>

                <div class="option-folder mt-2 p-2 border border-secondary rounded">
                    <div>
                        Based on your configuration, REDCap will be accessible at:
                        <a target="_blank" href="<?php echo $RI->redcap_webroot_url ?>">
                            <?php echo $RI->redcap_webroot_url ?>
                        </a> once complete.
                        <small class="form-text text-muted">This configuration can be changed by modifying the <strong>REDCAP_WEBROOT_PATH</strong> option in the <code>.env</code> file.</small>
                    </div>
                </div>

                Would you like to prepopulate with table-based users and activate table authentication?
                    <div class="form-check">
                        <label class="form-check-label">
                            <input type="checkbox" value=1 class="form-check-input" name="init-table">
                            Yes
                        </label>
                    </div>

            </div>

            <div class="card-footer">
                <input type="hidden" name="install-option"/>
                <button type="submit"
                        class="input-group btn btn-lg btn-success initiate-installation text-center d-block">INSTALL
                    REDCAP
                </button>
                <small class="form-text text-muted text-center">Any existing files will be overwritten. This make time some time to
                    download and extract... Be patient!
                </small>
            </div>
        </div>

        <div class="text-center mt-4">
            This installer was created by Andy Martin with assistance from Rob Taylor and Philip Chase
        </div>

    </form>
    <?php
} elseif ($RI->step == 2) {
    // DATABASE SETUP TBD
    ?>
    <?php
    }

// END OF PAGE
?>
</div>


<!-- Modal -->
<div class="modal fade" id="loading" tabindex="-1" role="dialog" aria-labelledby="loadingLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="loader"></div>
                <div class="loader-txt">
                    <p>Building your REDCap Server</p>
                    <small>This can take a few minutes...</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    $(document).ready(function(){

        // Handle upload option
        $('input[name="dl-option"]').bind('click', function() {
            var option = $(this).val();

            // Save selected option
            $('input[name="install-option"]').val(option);

            // Show parameters
            $('div.install-option').hide();
            // $('div.install-option.option-folder').fadeIn();
            $('div.option-' + option).fadeIn();

            // Show the installation option
            $('.card-footer').fadeIn();
        });


        // Update the bootstrap file uploader
        $('input[type="file"]').change(function(e){
            var fileName = e.target.files[0].name;
            $('.custom-file-label').html(fileName);
        });


        // Handle Loader
        $('button.initiate-installation').click(function(evt){
            evt.preventDefault();

            $("#loading").modal({
                backdrop: "static",     //remove ability to close modal with click
                keyboard: false,        //remove option to close with keyboard
                show: true              //Display loader!
            });

            setTimeout( function(){
                $('#form-upload').submit();
            }, 100 );
        });


        // Create a select2 box for the versions
        $('select[name="version"]')
            .css({"width":"100%"})
            .select2({
                'theme': 'bootstrap4'
            });

    });

</script>



<pre>
    <?php
        // DEBUGGING INFO
        foreach ($RI->debug as $k => $v) {
            echo "\n\n" . $k ."\n". print_r($v,true);
        }
    ?>
</pre>

</body>
</html>
