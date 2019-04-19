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
# - L@ST MODIFIED: 2019-04-19 10:41 on marvin
# -------------------------------------------------------------------


class Exercise {

    private $id;
    private $dir;
    private $exerciseStr = "Exercise not loaded yet";

    function __construct($id, $dir = "exercises") {
        # Store exercise ID
        $this->id  = $id;
        $this->dir = $dir;

        # Loading the exercise
        $this->exerciseStr = $this->load_exercise();
    }

    function __toString() {
        $msg = "";
        $msg += sprintf("Exercise ID:  %d\n", $this->id);
        $msg += sprintf("Exercise dir: %s\n", $this->dir);
        return($msg);
    }

    private function load_exercise() {
        $file = sprintf("%s/ex_%d.html", $this->dir, $this->id);
        if (!file_exists($file)) {
            return(sprintf("ERROR: Cannot find exercise \"%s\"", $file));
        }
        # Else loading the file and return content
        return(file_get_contents($file));
    }

    public function show() {
        print($this->exerciseStr);
    }
}
