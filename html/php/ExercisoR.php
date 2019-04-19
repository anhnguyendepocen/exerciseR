<?php

class ExercisoR {

    public $DbHandler    = NULL;
    public $LoginHandler = NULL;
    private $post = NULL;

    function __construct() {

        require("DbHandler.php");
        require("ExerciseHandler.php");
        require("LoginHandler.php");

        $this->DbHandler    = new DbHandler();

        // Create LoginHandler, does login check on construct
        $this->LoginHandler = new LoginHandler($this->DbHandler);

        // Exercise handler
        $this->ExerciseHandler = new ExerciseHandler($this->DbHandler);

        // Store _POST object
        $this->post = (object)$_POST;

    }

    /* Show content
     * If $this->post->exercise is empty, show user exercise
     * overview.
     */
    public function show_content() {
        if (!property_exists($this->post, "exercise")) {
            $this->_show_content_overview();
        } else {
            $this->_show_exercise($this->post->exercise);
        }
    }

    private function _show_content_overview() {

        // Loading open and finished exercises
        $open     = $this->ExerciseHandler->open_exercises();
        $finished = $this->ExerciseHandler->finished_exercises();

        // Open bootstrap container
        print("<div class=\"container\">\n");

        // Show open exercises
        print("<h3>Open exercises</h3>\n");

        function get_btn($hash, $status) {
            $btn = "<button type=\"submit\" class=\"btn %s\">%s</button>\n";
            if ($status == 0) {
                $btn = sprintf($btn, "btn-primary", "Solve");
            } else if ($status == 9) {
                $btn = sprintf($btn, "btn-success", "Finished");
            } else {
                $btn = sprintf($btn, "btn-secondary", "Continue");
            }
            $res = "<form name=\"%s\" method=\"POST\">\n"
                  ."  <input type=\"hidden\" name=\"exercise\" value=\"%s\" />\n"
                  ."  %s\n"
                  ."</form>\n";
            return(sprintf($res, $hash, $hash, $btn));
        }

        if (is_null($open)) {
            print("<div class=\"alert alert-info\">Currently no open exercises.</div>\n");
        } else {

            // Open table
            print("  <table class=\"table\">\n"
                 ."    <thead>\n"
                 ."      <tr>\n"
                 ."        <th>Added</th>\n"
                 ."        <th>Name</th>\n"
                 ."        <th></th>\n"
                 ."      </tr>\n"
                 ."    </thead>\n"
                 ."    <tbody>\n");


            // Show exercises
            $template = "     <tr>\n"
                       ."       <td>%s</td>\n"
                       ."       <td>%s</td>\n"
                       ."       <td>%s</td>\n"
                       ."     </tr>\n";

            foreach($open as $rec) {
                printf($template, $rec->created, $rec->name,
                       get_btn($rec->hash, $rec->status));
            }

            // Close table
            print("    </tbody>\n"
                 ."  </table>\n");

        } # End of open exercises

        // Close bootstrap container
        print("</div>\n");
    }

    private function _show_exercise($hash) {

        // Loading exercise ID
        $sql = "SELECT exercise_id FROM exercise_mapping WHERE hash = \"%s\";";
        $res = $this->DbHandler->query(sprintf($sql, $hash))->fetchArray(SQLITE3_ASSOC);

        if (count($res) == 0) { die("Whoops, problems finding the exercise!"); }

        // Else we have the exercise ID, show exercise
        $exercise = $this->ExerciseHandler->show_exercise($hash, $res["exercise_id"]);

    }





}
    

