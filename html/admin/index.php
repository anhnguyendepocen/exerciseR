<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("../php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

# Loading required config
$config = new ConfigParser("../../files/config.ini", "..");

# Loading the exercise class
$HandlerOptions = array("js"=>array("../lib/exr_admin.js"));
$Handler = new AdmineR($config, $HandlerOptions, true);
$Handler->site_show_header();

# Used to load files from the "files" folder ont accessible
# by the webserver.
?>

    <!-- CodeMirror -->
    <script>
        // http://simpleupload.michaelcbrook.com/
        $(document).ready(function(){
            // Setting up groups table
            $("#admin-table-groups").admin_table_groups(10);
            // Setting up user table
            $("#admin-table-exercises").admin_table_exercises(10);
            // Setting up user table
            $("#admin-table-users").admin_table_users(10);
        });
    </script>


    <div class="container" id="admin-add-users">

        <h3 style="padding-bottom: 1em;">Admin Start Page (Quick Overview)</h3>

        <p>
        This page shows a quick overview of latest activities (latest users added,
        latest groups created, latest exercises generated, ...).
        </p>

        <!-- tab navigation -->
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-exercises">Exercises</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-users">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-groups">Groups</a>
            </li>
        </ul>
        <br />

        <!-- tab panes -->
        <div class="tab-content">
            <!-- exercises -->
            <div class="tab-pane container active" id="tab-exercises">
                <p>
                    Shows newest 10 exercises.
                    To administrate exercises <a href="exercises.php" target="_self">click here</a>.
                </p>
                <div class="table-responsive" id="admin-table-exercises"></div>
            </div>
            <!-- users -->
            <div class="tab-pane container" id="tab-users">
                <p>
                    Shows the newest 10 users.
                    To administrate users <a href="users.php" target="_self">click here</a>.
                </p>
                <div class="table-responsive" id="admin-table-users"></div>
            </div>
            <!-- gorups -->
            <div class="tab-pane container" id="tab-groups">
                <p>
                    The latest 10 groups are shown.
                    To administrate groups <a href="groups.php" target="_self">click here</a>.
                </p>
                <div class="table-responsive" id="admin-table-groups"></div>
            </div>
        </div>

    </div>

<?php $Handler->site_show_footer(); ?>

