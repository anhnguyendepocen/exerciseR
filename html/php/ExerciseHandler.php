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
# - L@ST MODIFIED: 2019-04-19 17:12 on marvin
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
              ."ON em.exercise_id = e.exercise_id WHERE em.user_id = %d AND em.status %s %d";
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

        $file = sprintf("%s/ex_%d.html", $this->dir, $exercise_id);
        if (!file_exists($file)) {
            die(sprintf("ERROR: Cannot find exercise \"%s\"", $file));
        }
        # Else loading the file and return content
        $exercise = file_get_contents($file);

        # Expecting the script here:
        $file = sprintf("%s/main.R", $userdir);

        # Store destination (used in upload.php)
        $_SESSION["upload_file_destination"] = $file;
        if (file_exists($file)) {
            $script = file_get_contents($file);
        } else {
            $script = "# Nothing uploaded yet!";
        }
        ?>
        <div class="container">
        
            <div class="row">
              <div class="col-sm-12">
                  <h2>Exercise</h2>
                  <?php print($exercise); ?>
              </div>
            </div>
        
            <div class="row">
                <div class="col-sm-12">
                    <h2>Your code/script</h2>
                    This is the content of your current script file which will
                    be executed/tested. You can update the file by uploading
                    a new <i>R</i> script.
                    <br />
                    <h4>Upload File</h4>
                    <div id="filename"></div>
                    <div id="progress"></div>
                    <div id="progressBar"></div>
                    <input type="file" name="file">
                </div>
                <div class="col-sm-12" style="padding-top: 2em;">
                    <textarea id="editor">
                    <?php print($script); ?>
                    </textarea>
                </div>
            </div>
        </div>
        <?php
    }


}











