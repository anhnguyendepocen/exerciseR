<?php

/* Main handling class for the exerciseR interface.
 */
class ExerciseR {

    public $LoginHandler = NULL;
    public $DbHandler    = NULL;
    public $UserClass    = NULL;

    public $_post        = NULL;
    public $_get         = NULL;
    protected $_config      = NULL;
    protected $_options     = NULL;
    protected $ExerciseHandler = NULL;

    // Use 'true' for admin pages, 'false' for participant pages.
    // Used to check if the current user has enough privileges
    // to see this page.
    protected $admin_page = false;

    // Bool, whether or not this is a public page (no login required)
    protected $public     = false;

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
     * public : bool
     *      if false login is required, else it is a public page.
     */
    function __construct($config, $public = false, $options = NULL, $admin_page = false) {

        // Default time zone handling
        date_default_timezone_set("UTC");
        $this->admin_page = $admin_page;

        // Append options
        $this->_options     = $options;

        // Append ConfigParser object
        $this->config       = $config;

        $this->DbHandler = new DbHandler($config);

        $this->LoginHandler = new LoginHandler($this->DbHandler, $config, $public);

        // Create LoginHandler, does login check on construct
        if (!$public | isset($_SESSION["user_id"])) {
            // If logn was successful: add UserClass to the object
            $this->UserClass = new UserClass($_SESSION["user_id"], $this->DbHandler);

            // If this is an admin page but the user has no admin privileges.
            // show an error.
            if ($this->admin_page & !$this->UserClass->is_admin()) {
                $this->LoginHandler->access_denied();
            }
        } else {

            // Create an object for a public user.
            $this->UserClass = new UserClass(NULL, NULL);

        }

        // Store _POST object
        $this->_post = (object)$_POST;
        // Get argument(s): used for public exercise URL's
        $this->_get  = (object)$_GET;

        // Exercise handler
        $this->ExerciseHandler = new ExerciseHandler($config, $this->DbHandler);

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
    <link rel="stylesheet" href="css/bootstrap.4.1.2.min.css" />
    <link rel="stylesheet" href="css/exerciseR.css" />
    <script src="lib/jquery-3.4.1.min.js"></script>
    <script src="lib/bootstrap-4.2.1.min.js"></script>
    <?php
    // Adding js scripts
    if (isset($this->_options["js"])) {
        foreach($this->_options["js"] as $val) {
            printf("    <script src=\"%s\"></script>\n", $val);
        }
    }
    // Adding css style files
    if (isset($this->_options["css"])) {
        foreach($this->_options["css"] as $val) {
            printf("    <link rel=\"stylesheet\" href=\"%s\" />\n", $val);
        }
    }
    ?>
</head>
<body>

    <nav id="top-nav" class="navbar navbar-expand-sm bg-primary navbar-light">
        <a href="/" target="_self">
            <img id="exerciserlogo" src="css/logo.svg"></img>
        </a>
        <ul class="navbar-nav">
            <?php  if (isset($_SESSION["user_id"])) { ?>
            <li class="nav-item">
                <a class="nav-link text-light" href="index.php">exercises</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="profile.php">profile</a>
            </li>
            <?php } ?>
            <li class="nav-item">
                <a class="nav-link text-light" href="about.php">about</a>
            </li>
            <?php
            // If a user_id is set (current session):
            if (isset($_SESSION["user_id"])) {
                // Check if user has administrator privileges
                if ($_SESSION["user_id"] == $_SESSION["loggedin_as"]) {
                    // If the user is logged in as himself/herself
                    $is_admin = $this->UserClass->is_admin();
                } else {
                    // Else check the permissions of the 'loggedin_as' user.
                    $UserCls = new UserClass($_SESSION["loggedin_as"], $this->DbHandler);
                    $is_admin = $UserCls->is_admin();
                }
                // Show admin link in the top navigation bar if $is_admin is true.
                if ($is_admin) { ?>
                <!-- logout button -->
                <li class="nav-item">
                    <a class="nav-link text-light" href="admin/">admin</a>
                </li>
                <?php } ?>
                <!-- logout button -->
                <li class="nav-item">
                    <?php $this->LoginHandler->logout_form($this->UserClass); ?>
                </li>
            <?php } else { ?>
                <!-- not logged in: show login link in menu -->
                <li class="nav-item">
                    <a class="nav-link text-light" href="login.php">login</a>
                </li>
            <?php } ?>
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

        
        // Adding rows
        foreach($x as $rec) {
            $rec->created  = $this->_date_to_string($rec->created);
            $rec->run_last = $this->_date_to_string($rec->run_last);
            if ($rec->run_counter == 0) {
                $rec->run_counter = "";
            } else if ($rec->run_counter == 1) {
                $rec->run_counter = "tried once";
            } else {
                $rec->run_counter = sprintf("tried %d times", $rec->run_counter);
            }
            $rec->run_counter = sprintf("<br /><span class=\"text-muted\">"
                                           ."%s</span>", $rec->run_counter);
            // Show entry
            printf($template, $rec->created, $rec->name,
                   $this->get_btn($rec->hash, $rec->status, false),
                   $rec->run_last, $rec->run_counter);
        }

        // Close table
        print("    </tbody>\n"
             ."  </table>\n");
    }

    /* Helper function to create the table of public exercises.
     *
     * Parameters
     * ==========
     * x : stdClass
     *      Object containing public exercise information.
     *
     * Returns
     * =======
     * No return, outputs html for user frontend.
     */
    private function _show_public_list($x) {
        // Open table
        print("  <table class=\"table\">\n"
             ."    <thead>\n"
             ."      <tr>\n"
             ."        <th>Added</th>\n"
             ."        <th>Exercise Name</th>\n"
             ."        <th>Identifier</th>\n"
             ."        <th>&nbsp;</th>\n"
             ."      </tr>\n"
             ."    </thead>\n"
             ."    <tbody>\n");


        // Show exercises
        $template = "     <tr>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s</td>\n"
                   ."       <td>%s</td>\n"
                   ."     </tr>\n";

        foreach($x as $rec) {
            $rec->created  = $this->_date_to_string($rec->created);
            printf($template, $rec->created, $rec->name, $rec->identifier,
                   $this->get_btn($rec->identifier, NULL, true));
        }

        // Close table
        print("    </tbody>\n"
             ."  </table>\n");
    }

    // Helper function to generate buttons
    private function get_btn($hash, $status, $public = false) {
        if ($public) {
            $btn = sprintf("<button type=\"submit\" class=\"btn btn-primary\">Try</button>\n",
                            $status);
            $res = "<form action=\"exercise.php\" name=\"%1\$s\" method=\"GET\">\n"
                  ."  <input type=\"hidden\" name=\"public\" value=\"%1\$s\" />\n"
                  ."  %2\$s\n"
                  ."</form>\n";
            return(sprintf($res, $hash, $btn));
        } else {
            $status = $status == "assigned" ? "solve" : $status;
            $btn = sprintf("<button type=\"submit\" class=\"btn %1\$s\">%1\$s</button>\n",
                            $status);
            $res = "<form action=\"exercise.php\" name=\"%s\" method=\"POST\">\n"
                  ."  <input type=\"hidden\" name=\"exercise\" value=\"%s\" />\n"
                  ."  %s\n"
                  ."</form>\n";
            return(sprintf($res, $hash, $hash, $btn));
        }
    }

    /* Show index page (exercise overview)
     */
    public function show_index() {

        // Check if user is logged in.
        $logged_in = is_null($this->UserClass->user_id()) ? false : true;

        // Loading open and solved exercises
        if ($logged_in) {
            $open   = $this->ExerciseHandler->open_exercises();
            $solved = $this->ExerciseHandler->solved_exercises();
        } else {
            $public = $this->ExerciseHandler->public_exercises();
        }

        // Open bootstrap container
        print("<div class=\"container\">\n");

        // Show open exercises
        if ($logged_in) {
            print("<h3>Open exercises</h3>\n");
            if (is_null($open)) {
                print("<div class=\"alert alert-info\">Currently no open exercises.</div>\n");
            } else {
                $this->_show_list($open);
            }

            // Show open
            if (!is_null($solved)) {
                print("<h3>Finished exercises</h3>\n");
                $this->_show_list($solved);
            }
        } else {
            print("<h3>Open public exercises</h3>\n");
            if (is_null($public)) {
                print("<div class=\"alert alert-info\">Currently no open public exercises.</div>\n");
            } else {
                $this->_show_public_list($public);
            }
        }

        // Close bootstrap container
        print("</div>\n");
    }

    public function show_exercise() {

        // Public exercise?
        if (property_exists($this->_get, "public")) {

            // What we do in this case: set "hash" to "public".
            // Used by ExerciseHandler->show_exercise: in case the
            // hash is simply "public" a random temporary folder
            // will be created.
            $hash = "public";
            // Extract ID and identifier
            if(!preg_match("/^(.*)-([0-9]+){1,}$/", $this->_get->public, $res)) {
                print("<h3>Watch out, a problem occurred!</h3>");
                print("<p>Cannot extract exercise id from public identifier!<br/>"
                    . "Identifier \"<b>" . $this->_get->public ."</b>\" has wrong format.</p>");
                return;
            }
            // Check if this is a real public exercise
            $res = $this->DbHandler->query("SELECT * FROM public_exercises WHERE "
                                          .sprintf("exercise_id = %d ", $res[2])
                                          .sprintf("AND identifier = \"%s\"", $res[1]));
            // If not equal to one, something is wrong
            if (!$res->num_rows == 1) {
                print("<h3>Oh dear, something went wrong!</h3>");
                print("<p>Problems to find the public exercise \"<b>"
                    . $this->_get->public . "</b></p>\".");
                return;
            }
            $res = $res->fetch_object();
            // Status no longer active?
            if (strcmp($res->status, "active") != 0) {
                print("<h3>Oh no!</h3>");
                print("<p>Public exercise \"<b>" . $this->_get->public
                    . "</b>\" no longer active.</p>");
                return;
            }
            // Setting id
            $id = $res->exercise_id;
            $status = $res->status;

        } else {
            // No exercise hash (via post)
            $hash = property_exists($this->_post, "exercise") ? $this->_post->exercise : NULL;
            // Loading exercise ID
            if (!is_null($hash)) {
                $sql = "SELECT exercise_id, status FROM exercise_mapping WHERE hash = \"%s\";";
                $res = $this->DbHandler->query(sprintf($sql, $hash))->fetch_object();
                if ($res->num_rows != 0) { die("Whoops, problems finding the exercise!"); }
                $id     = $res->exercise_id;
                $status = $res->status;
            } else {
                // Simply means "not found"
                $id     = NULL;
                $status = NULL;
            }
        }

        // Else we have the exercise ID, show exercise
        $exercise = $this->ExerciseHandler->show_exercise($hash, $id, $status);

    }





}
    

