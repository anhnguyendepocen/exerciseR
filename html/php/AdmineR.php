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
                <a class="nav-link text-light" href="../index.php">UI</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="index.php">home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="users.php">users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="groups.php">groups</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="exercises.php">exercises</a>
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



}
    

