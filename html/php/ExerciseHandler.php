<?php
# -------------------------------------------------------------------
# - NAME:        exercise.php
# - AUTHOR:      Reto Stauffer
# - DATE:        2019-04-19
# -------------------------------------------------------------------
# - DESCRIPTION:
# -------------------------------------------------------------------
# - EDITORIAL:   2019-04-19, RS: Created file on thinkreto.
# -------------------------------------------------------------------
# - L@ST MODIFIED: 2019-08-26 19:01 on marvin
# -------------------------------------------------------------------


class ExerciseHandler {

    private $dir;
    private $exerciseStr = "Exercise not loaded yet";
    private $db = NULL;
    private $config = NULL;

    function __construct($config, $db) {
        $this->config = $config;
        $this->db     = $db;

    }

    function __toString() {
        $msg = "";
        //$msg += sprintf("Exercise ID:  %d\n", $this->id);
        $msg += sprintf("Exercise dir: %s\n", $this->config->get("path", "exercises"));
        return($msg);
    }


    private function _load_open_finished_exercises($user_id = NULL, $status) {

        // Take SESSION user_id if not specified.
        if (is_null($user_id)) { $user_id = $_SESSION["loggedin_as"]; }
        // Fetching exercises
        $sql = "SELECT em.mapping_id, em.hash, e.name, em.created, em.run_counter, "
              ."em.run_last, em.status FROM exercise_mapping AS em "
              ."LEFT JOIN exercises AS e "
              ."ON em.exercise_id = e.exercise_id WHERE em.user_id = %d AND em.status %s "
              ."ORDER BY em.created DESC;";
        if ($status instanceof string) {
            $status = sprintf(" = \"%s\"", $status);
        } else if (is_array($status)) {
            for ($i = 0; $i < count($status); $i++) { $status[$i] = sprintf("\"%s\"", $status[$i]); }
            $status = sprintf(" IN (%s)", join(", ", $status));
        }
        // Open exercises
        $query = $this->db->query(sprintf($sql, $user_id, $status));

        // Create object
        $res = array();
        while($row = $query->fetch_object()) {
            array_push($res, $row);
        }
        return(count($res) == 0 ? NULL : (object)$res);
    }


    /* Loading open exercises for the current user.
     * Uses the _load_open_finished_exercises function.
     * 
     * Parameters
     * ==========
     * user_id : int or NULL
     *      if NULL the $_SESSION["loggedin_as"] is used. 
     *
     * Returns
     * =======
     * Returns a stdClass object containing the exercises.
     */
    public function open_exercises($user_id = NULL) {
        return($this->_load_open_finished_exercises($user_id, array("assigned", "retry")));
    }

    /* Loading finished exercises for the current user.
     * Uses the _load_open_finished_exercises function.
     * 
     * Parameters
     * ==========
     * user_id : int or NULL
     *      if NULL the $_SESSION["loggedin_as"] is used. 
     *
     * Returns
     * =======
     * Returns a stdClass object containing the exercises.
     */
    public function finished_exercises($user_id = NULL) {
        return($this->_load_open_finished_exercises($user_id, array("solved", "closed")));
    }


    private function _load_exercise_xml($file) {
        if (!is_file($file)) {
            die(sprintf("Cannot find requested file \"%s\".", $file));
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($file);
        if ($xml === false) {
            echo "Failed loading XML: ";
            foreach(libxml_get_errors() as $error) {
                echo "<br>", $error->message;
            }
            die(0);
        }
        return($xml);
    }

    /* Check if "included files" exist on disc (files shipped with
     * the exercise itself).
     *
     * Parameters
     * ==========
     * xml : SimpleXMLObject
     *      the exercise xml file.
     * path : str
     *      path where the files are located.
     */
    private function _check_xml_file_availability($xml, $dir) {
        if (!is_object($xml)) die("Wrong input to _check_xml_file_availability");
        if (!is_dir($dir)) die(sprintf("Cannot find directory \"%s\"", $dir));
        if (property_exists($xml->settings->files, "file")) {
            foreach($xml->settings->files->file as $file) {
                if (!is_file(join("/", array($dir, $file)))) {
                    printf("Cannot find file which should be shipped with the exercise. "
                          ."Name of the file: \"%s/%s\".", $dir, $file);
                    die();
                }
            }
        }
        return;
    }


    /* Show a specific exercise (description, ...)
     *
     * Parameters
     * ==========
     * hash : str
     *      hash for this specific exercise/current user. The hash is
     *      a combination of a random hash, the timestamp when the exercise
     *      has been assigned, and the user id. Used for the user uploads.
     * exercise_id : int
     *      ID of the exercise.
     *
     * Returns
     * =======
     * No return, just creates UI.
     */
    public function show_exercise($hash, $exercise_id) {

        // The files we will use
        $exdir = sprintf("%s/%d", $this->config->get("path", "exercises"), $exercise_id);
        $files = array("xml"         => sprintf("%s/%s", $exdir, "exercise.xml"),
                       "description" => sprintf("%s/%s", $exdir, "exercise_description.html"),
                       "solution"    => sprintf("%s/%s", $exdir, "exercise_solution.html"),
                       "tests"       => sprintf("%s/%s", $exdir, "exercise_tests.html"));

        # Check if files exist. If not, stop
        foreach($files as $key=>$val) {
            if (!is_file($val)) { die(sprintf("Cannot find file \"%s\".", $value)); }
        }

        // User directory. If not existing, create.
        $userdir  = sprintf("%s/user-%d/%s", $this->config->get("path", "uploads"),
                            $_SESSION["loggedin_as"], $hash);

        // Creates user upload directory and adds correct
        // permissions/group ownership if defined in the config file.
        if(!is_dir($userdir)) {
            // Load directory permission setting from config file.
            // If not set, the default of 0775 is used.
            $check = mkdir($userdir, 0775, true);
            if(!$check) { die("Problems creating the directory! Whoops."); }
            // Change group if specified in the config file.
            $group = $this->config->get("permissions", "group", false);
            if (!is_null($group)) { $check = chgrp($userdir, $group); }
            if(!$check) { die("Problems changing directory group ownership."); }
            // Change mode
            $mode  = $this->config->get("permissions", "dir", false);
            if (!is_null($mode)) {
                $check = chmod($userdir, octdec($mode));
                if(!$check) { die("Problems changing file mode bits."); }
            }
        }

        // Read exercise information
        $xml         = $this->_load_exercise_xml($files["xml"]);
        $description = file_get_contents($files["description"]);
        $solution    = file_get_contents($files["solution"]);
        $tests       = file_get_contents($files["tests"]);

        // If we have "Files included" (<file>...</file>) in the xml file,
        // check if these files are available.
        // TODO: error styling in the whole function.
        $this->_check_xml_file_availability($xml, $exdir);

        // Small helper function for meta-info output
        function show_ul($title, $class, $entries) {
            $str  = sprintf("<div style=\"clear: both;\">\n<b>%s</b>\n", $title);
            $str .= sprintf("<ul class=\"exerciser %s\">", $class);
            foreach($entries as $x) { $str .= sprintf("  <li>%s</li>\n", $x); }
            $str .= "</ul>\n</div>";
            print($str);
        }
        ?>

        <div class="row">
          <div class="col-md-12" id="title">
              <h1><?php print($xml->settings->title); ?></h1>
          </div>
          <div class="col-md-9" id="description">
              <?php print($description); ?>
          </div>
          <div class="col-md-3" id="metainfo">
            <h1>Metainfo</h1>
            <?php
            if (property_exists($xml->settings->blacklist, "cmd")) {
                show_ul("Blacklisted", "cmd blacklist", $xml->settings->blacklist->cmd);
            }
            if (property_exists($xml->settings->whitelist, "cmd")) {
                show_ul("Whitelisted", "cmd whitelist", $xml->settings->whitelist->cmd);
            }
            if (property_exists($xml->settings->files, "file")) {
                show_ul("Files included", "exercise files", $xml->settings->files->file);
            }
            ?> 
          </div>
        </div>
        <div class="row">
          <div class="col-md-12" id="tests">
            <?php print($tests); ?> 
          </div>
        </div>

        <?php
        # Expecting the script here:
        $file    = sprintf("%s/main.R", $userdir);

        # Store destination (used in upload.php)
        $_SESSION["exercise_hash"]           = $hash;
        $_SESSION["exercise_id"]             = $exercise_id;
        $_SESSION["upload_file_destination"] = $file;
        if (file_exists($file)) {
            $script = file_get_contents($file);
            $btn_run_class = "btn-success";
            $btn_run_disabled = false;
        } else {
            $script = "# Nothing uploaded yet!";
            $btn_run_class = "btn-secondary";
            $btn_run_disabled = true;
        }

        # Docker log
        $logfile = sprintf("%s/main.log", $userdir);
        if (file_exists($logfile)) {
            $ocpulog = file_get_contents($logfile);
        } else {
            $ocpulog = "# No logs yet ...";
        }

        ?>

        <div class="row" style="margin-top: 2em;">
            <div class="col-sm-5">
                <h4>Upload R Script</h4>
                <div id="filename"></div>
                <div id="progress"></div>
                <div id="progressBar"></div>
                <input type="file" name="file" class="form-control-file border" />
            </div>
            <div class="col-sm-4">
                <h4>Run the Script</h4>
                <button id="btn-run" type="button"
                    class="btn <?php print $btn_run_class; ?>"
                    <?php print($btn_run_disabled ? "disabled" : ""); ?>>Run</button>
            </div>
            <div class="col-sm-3" id="ocpu_summary">
                <div class="round"
                          data-value="0.0"
                          data-size="150"
                          data-thickness="10">
                          <strong></strong>
                          <span>Success rate</span>
                </div>
                <script>
                /* Code By Webdevtrick ( https://webdevtrick.com ) */
                function Circlle(el){
                  $(el).circleProgress({fill: {color: "#28a745"}})
                    .on("circle-animation-progress", function(event, progress, stepValue){
                        $(this).find("strong").text(String(Math.round(stepValue * 100)) + "%");
                        //$(this).find("strong").text(String(stepValue.toFixed(3)).substr(3)+"%");
                        });  
                };
                Circlle('#ocpu_summary .round');
                </script>
            </div>
        </div>

        <div class="row" style="margin-top: 2em;">
            <!-- tab navigation -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-script">Script</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-ocpuoutput">Output</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-ocpulog">Log</a>
                </li>
            </ul>
            <br />

            <!-- tab contents -->
            <div class="tab-content" style="width: 100%; float: none;">
                <div class="tab-pane container active" id="tab-script">
                    This is the content of your R script:
                    <textarea id="script">
                    <?php print($script); ?>
                    </textarea>
                </div>
                <div class="tab-pane container fade" id="tab-ocpuoutput">
                    <div class="alert alert-info">No message yet ...</div>
                    <br />
                    <div id="ocpuoutput">
                    No output produced so far.
                    </div>
                </div>
                <div class="tab-pane container fade" id="tab-ocpulog">
                    <div class="alert alert-info">No message yet ...</div>
                    <br />
                    <textarea id="ocpulog">
                    <?php print($ocpulog); ?>
                    </textarea>
                </div>
            </div>
        </div>


        <?php
    }


}











