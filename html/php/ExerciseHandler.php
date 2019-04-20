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
# - L@ST MODIFIED: 2019-04-20 14:05 on marvin
# -------------------------------------------------------------------


class ExerciseHandler {

    private $dir;
    private $exerciseStr = "Exercise not loaded yet";
    private $db = NULL;

    function __construct($db, $dir = "exercises") {
        # Store exercise ID
        $this->dir = $dir;
        $this->db  = $db;

    }

    function __toString() {
        $msg = "";
        //$msg += sprintf("Exercise ID:  %d\n", $this->id);
        $msg += sprintf("Exercise dir: %s\n", $this->dir);
        return($msg);
    }


    private function _load_open_finished_exercises($user_id = NULL, $operator, $status) {

        // Take SESSION user_id if not specified.
        if (is_null($user_id)) { $user_id = $_SESSION["user_id"]; }
        // Fetching exercises
        $sql = "SELECT em.mapping_id, em.hash, e.name, em.created, em.run_counter, "
              ."em.run_last, em.status FROM exercise_mapping AS em "
              ."LEFT JOIN exercises AS e "
              ."ON em.exercise_id = e.exercise_id WHERE em.user_id = %d AND em.status %s %d "
              ."ORDER BY em.created DESC;";
        // Open exercises
        $query = $this->db->query(sprintf($sql, $user_id, $operator, $status));

        // Create object
        $res = array();
        while($row = $query->fetchArray(SQLITE3_ASSOC)) {
            array_push($res, (object)$row);
        }
        return(count($res) == 0 ? NULL : (object)$res);
    }


    /* Loading open exercises for the current user.
     * Uses the _load_open_finished_exercises function.
     * 
     * Parameters
     * ==========
     * user_id : int or NULL
     *      if NULL the $_SESSION["user_id"] is used. 
     *
     * Returns
     * =======
     * Returns a stdClass object containing the exercises.
     */
    public function open_exercises($user_id = NULL) {
        return($this->_load_open_finished_exercises($user_id, "<", 9));
    }

    /* Loading finished exercises for the current user.
     * Uses the _load_open_finished_exercises function.
     * 
     * Parameters
     * ==========
     * user_id : int or NULL
     *      if NULL the $_SESSION["user_id"] is used. 
     *
     * Returns
     * =======
     * Returns a stdClass object containing the exercises.
     */
    public function finished_exercises($user_id = NULL) {
        return($this->_load_open_finished_exercises($user_id, ">=", 9));
    }




    public function show_exercise($hash, $exercise_id) {

        // Exercise directory
        $userdir  = sprintf("uploads/user-%d/%s", $_SESSION["user_id"], $hash);
        if(!is_dir($userdir)) {
            $check = mkdir($userdir, 0777, true);
            if(!$check) { die("Problems creating the directory! Whoops."); }
        }

        $file = sprintf("%s/%d/exercise.html", $this->dir, $exercise_id);
        if (!file_exists($file)) {
            die(sprintf("ERROR: Cannot find exercise \"%s\"", $file));
        }
        # Else loading the file and return content
        $exercise = file_get_contents($file);

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
            $dockerlog = file_get_contents($logfile);
        } else {
            $dockerlog = "# No logs yet ...";
        }

        ?>

        <div class="row">
          <div class="col-sm-12">
              <?php print($exercise); ?>
          </div>
        </div>
        
        <div class="row" style="margin-top: 2em;">
            <div class="col-sm-6">
                <h4>Upload R Script</h4>
                <div id="filename"></div>
                <div id="progress"></div>
                <div id="progressBar"></div>
                <input type="file" name="file" class="form-control-file border" />
            </div>
            <div class="col-sm-6">
                <h4>Run the Script</h4>
                <button id="btn-run" type="button"
                    class="btn <?php print $btn_run_class; ?>"
                    <?php print($btn_run_disabled ? "disabled" : ""); ?>>Run</button>
            </div>
        </div>

        <div class="row" style="margin-top: 2em;">
            <!-- tab navigation -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#scripttab">Script</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#logtab">Log</a>
                </li>
            </ul>
            <br />

            <!-- tab contents -->
            <div class="tab-content" style="width: 100%; float: none;">
                <div class="tab-pane container active" id="scripttab">
                    This is the content of your R script:
                    <textarea id="script">
                    <?php print($script); ?>
                    </textarea>
                </div>
                <div class="tab-pane container fade" id="logtab">
                    <div class="alert alert-info">No message yet ...</div>
                    <br />
                    <textarea id="dockerlog">
                    <?php print($dockerlog); ?>
                    </textarea>
                </div>
            </div>
        </div>


        <?php
    }


}











