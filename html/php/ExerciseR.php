<?php

/* Main handling class for the exerciseR interface.
 */
class ExerciseR {

    public $LoginHandler = NULL;
    public $DbHandler    = NULL;
    public $UserClass    = NULL;

    private $post        = NULL;
    private $config      = NULL;
    private $ExerciseHandler = NULL;

    // Use 'true' for admin pages, 'false' for participant pages.
    // Used to check if the current user has enough privileges
    // to see this page.
    private $admin_page = false;

    /* Setting up ExerciseR object.
     *
     * The ExerciseR object is the main object for the UI/UX.
     * Sets up the database connection, grants permission (LoginHandler)
     * to the UI, and loads user information (UserClass).
     * In addition, an ExerciseHandler object is initialized (handling
     * Exercises, Overview, ...).
     *
     * Parameters
     * ==========
     * config : ConfigParser
     *      object with UI/UX configuration.
     */
    function __construct($config, $admin_page = false) {

        // Default time zone handling
        date_default_timezone_set("UTC");
        $this->admin_page = $admin_page;

        // Append ConfigParser object
        $this->config       = $config;

        require("DbHandler.php");
        $this->DbHandler = new DbHandler($config);

        // Create LoginHandler, does login check on construct
        require("LoginHandler.php");
        $this->LoginHandler = new LoginHandler($this->DbHandler);

        // If logn was successful: add UserClass to the object
        require("UserClass.php");
        $this->UserClass = new UserClass($_SESSION["user_id"], $this->DbHandler);

        // If this is an admin page but the user has no admin privileges.
        // show an error.
        if ($this->admin_page & !$this->UserClass->is_admin()) {
            $this->LoginHandler->access_denied();
        }

        // Exercise handler
        require("ExerciseHandler.php");
        $this->ExerciseHandler = new ExerciseHandler($config, $this->DbHandler);

        // Store _POST object
        $this->post = (object)$_POST;

    }

    /* Show page header
     *
     * Creates the html document head including navigation.
     *
     * Returns
     * =======
     * No return, creates html output (header).
     */
    public function site_show_header() {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>exerciseR</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/exerciseR.css">
    <script src="lib/jquery-3.4.1.min.js"></script>
    <script src="lib/bootstrap-4.2.1.min.js"></script>
</head>
<body>

    <nav id="top-nav" class="navbar navbar-expand-sm bg-primary navbar-light">
        <img id="exerciserlogo" src="css/logo.svg"></img>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-light" href="index.php">exercises</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="profile.php">profile</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="about.php">about</a>
            </li>
            <?php if ($this->UserClass->is_admin()) { ?>
            <li class="nav-item">
                <a class="nav-link text-light" href="admin/">admin</a>
            </li>
            <?php } ?>
            <li class="nav-item">
                <?php $this->LoginHandler->logout_form($this->UserClass); ?>
            </li>
        </ul>
    </nav>

    <!-- content container -->
    <div class="container">

        <?php
    }

    /* Page Footer
     */
    public function site_show_footer() {
        ?>
    <!-- end of content -->
    </div>
    <div id="footer" class="bg-light">
        <div class="container">
            <div class="row">
                <div class="col-6 text-muted">
                    <b>exciseR</b><br />
                    This project is currently in an alpha
                    state and more a proof-of-concept
                    than a running tool.<br />
                    <span class="Rlogo"></span>
                    <span class="opencpulogo"></span>
                </div>
                <div class="col-6 text-muted">
                    <b>Contact:</b><br />
                    e-Mail: <a class="text-muted" href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#82;&#101;&#116;&#111;&46;&#83;&#116;&#97;&#117;&#102;&#102;&#101;&#114;&#64;&#117;&#105;&#98;&#107;&#46;&#97;&#99;&#46;&#97;&#116;">&#82;&#101;&#116;&#111;&#46;&#83;&#116;&#97;&#117;&#102;&#102;&#101;&#114;&#64;&#117;&#105;&#98;&#107;&#46;&#97;&#99;&#46;&#97;&#116;</a><br />
                    website: <a class="text-muted" href="https://retostauffer.org">https://retostauffer.org</a><br />
                    github:  <a class="text-muted" href="https://github.com/retostauffer/exerciseR">exerciseR repository</a>
                </div>
            </div>
        </div>
                
    </div>
</body>
</html>
        <?php
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


    /* Convert times to string
     * Parameters
     * ==========
     * x : string or integer
     *      if string, will be converted to unix time stamp (integer)
     *
     * Returns
     * =======
     * Returns a html <span> element with colorized human readable
     * date string.
     */
    private function _date_to_string($x) {
        // Current time stamp
        $now = time();
        // Convert to unix time stamp if needed
        if (is_string($x)) { $x = strtotime($x); }
        // If $x is 0: never executed (return "never")
        if ($x == 0) {
            $str = "never";
            $cls = "text-warning";
        } else {
            $str = strftime("%b %d, %Y", $x);
            if ($x > ($now - 3 * 86000)) {
                $cls = "text-primary";
            } else {
                $cls = "text-secondary";
            }
        }
        return(sprintf("<span class=\"%s\">%s</span>", $cls, $str));
    }


    /* Helper function to create the table
     *
     * Parameters
     * ==========
     * x : stdClass
     *      Object containing exercise information.
     *
     * Returns
     * =======
     * No return, outputs html for user frontend.
     */
    private function _show_list($x) {
        // Open table
        print("  <table class=\"table\">\n"
             ."    <thead>\n"
             ."      <tr>\n"
             ."        <th>Added</th>\n"
             ."        <th>Exercise Name</th>\n"
             ."        <th>Status</th>\n"
             ."        <th>Last Executed</th>\n"
             ."      </tr>\n"
             ."    </thead>\n"
             ."    <tbody>\n");


        // Show exercises
        $template = "     <tr>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s%s</td>\n"
                   ."     </tr>\n";


        foreach($x as $rec) {
            $rec->created  = $this->_date_to_string($rec->created);
            $rec->run_last = $this->_date_to_string($rec->run_last);
            if ($rec->run_counter == 0) {
                $rec->run_counter = "";
            } else {
                $rec->run_counter = sprintf("<br /><span class=\"text-muted\">"
                                           ."tried %d times</span>", $rec->run_counter);
            }
            // Show entry
            printf($template, $rec->created, $rec->name,
                   get_btn($rec->hash, $rec->status),
                   $rec->run_last, $rec->run_counter);
        }

        // Close table
        print("    </tbody>\n"
             ."  </table>\n");
    }


    private function _show_content_overview() {

        // Loading open and finished exercises
        $open     = $this->ExerciseHandler->open_exercises();
        $finished = $this->ExerciseHandler->finished_exercises();

        // Open bootstrap container
        print("<div class=\"container\">\n");

        // Helper function to generate buttons
        function get_btn($hash, $status) {
            $status = $status == "assigned" ? "solve" : $status;
            $btn = sprintf("<button type=\"submit\" class=\"btn %1\$s\">%1\$s</button>\n",
                            $status);
            $res = "<form name=\"%s\" method=\"POST\">\n"
                  ."  <input type=\"hidden\" name=\"exercise\" value=\"%s\" />\n"
                  ."  %s\n"
                  ."</form>\n";
            return(sprintf($res, $hash, $hash, $btn));
        }

        // Show open exercises
        print("<h3>Open exercises</h3>\n");
        if (is_null($open)) {
            print("<div class=\"alert alert-info\">Currently no open exercises.</div>\n");
        } else {
            $this->_show_list($open);
        }

        // Show open
        if (!is_null($finished)) {
            print("<h3>Finished exercises</h3>\n");
            $this->_show_list($finished);
        }

        // Close bootstrap container
        print("</div>\n");
    }

    private function _show_exercise($hash) {

        // Loading exercise ID
        $sql = "SELECT exercise_id FROM exercise_mapping WHERE hash = \"%s\";";
        $res = $this->DbHandler->query(sprintf($sql, $hash))->fetch_object();

        if ($res->num_rows != 0) { die("Whoops, problems finding the exercise!"); }

        // Else we have the exercise ID, show exercise
        $exercise = $this->ExerciseHandler->show_exercise($hash, $res->exercise_id);

    }





}
    

