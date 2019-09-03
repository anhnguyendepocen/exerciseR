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

// Loading configuration
$config = new ConfigParser("../../files/config.ini");

# Loading the exercise class
$Handler = new ExerciseR($config);

if (!$Handler->UserClass->is_admin()) {
    throw new Exception("You are not an administrator! Access denied");
}

# If not admin: deny access.
$_SESSION["loggedin_as"] = (int)$_REQUEST["user_id"];
$user = new UserClass($_REQUEST["user_id"], $Handler->DbHandler);
$_SESSION["username"] = $user->username();
header("Location: ../index.php");
