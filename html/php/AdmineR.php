<?php

/* Main handling class for the exerciseR interface.
 */
class AdmineR extends ExerciseR {

    // All admin pages need to have "true".
    // Used to check if the current user has enough privileges
    // to see this page.
    protected $admin_page = true;

    /* Overruling UI header
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
    <link rel="stylesheet" href="../css/bootstrap.4.1.2.min.css" />
    <link rel="stylesheet" href="../css/exerciseR.css" />
    <script src="../lib/jquery-3.4.1.min.js"></script>
    <script src="../lib/bootstrap-4.2.1.min.js"></script>
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

    <nav id="top-nav" class="navbar navbar-expand-sm bg-secondary navbar-light">
        <img id="exerciserlogo" src="../css/logo.svg"></img>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-light" href="users.php">users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="../">back</a>
            </li>
            <li class="nav-item">
                <?php $this->LoginHandler->logout_form($this->UserClass); ?>
            </li>
        </ul>
    </nav>

    <!-- content container -->
    <div class="container">

        <?php
    }

    /* Show Admin Index Page */
    public function show_admin_index() {

        // Open bootstrap container
        ?> 
        <script>
        $(document).ready(function() {
            // Setting up groups table
            $("#admin-table-groups").admin_table_groups(5);
            // Setting up user table
            $("#admin-table-exercises").admin_table_exercises(10);
            // Setting up user table
            $("#admin-table-users").admin_table_users(10);
        });
        </script>

        <div class="container">
            <h3>Groups</h3>
            <div class="table-responsive" id="admin-table-groups"></div>
            
            <h3>Exercises</h3>
            <div class="table-responsive" id="admin-table-exercises"></div>

            <h3>Users</h3>
            <div class="table-responsive" id="admin-table-users"></div>
        </div>
        <?php

    }

    public function show_admin_exercises() {

        // Open bootstrap container
        ?> 
        <script>
        $(document).ready(function() {
            var groups = $.fn.getData({what: "groups", limit: 10});
            console.log(groups)
        });
        </script>
        <div class="container">
        Exercises ...

        <div id="admin-exercises"></div>
        </div>
        <?php

    }


}
    

