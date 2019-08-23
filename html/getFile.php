<?php
# -------------------------------------------------------------------
# - NAME:        getFile.php
# - AUTHOR:      Reto Stauffer
# - DATE:        2019-08-22
# -------------------------------------------------------------------
# - DESCRIPTION: File downloader.
# -------------------------------------------------------------------
# - EDITORIAL:   2019-08-22, RS: Created file on thinkreto.
# -------------------------------------------------------------------
# - L@ST MODIFIED: 2019-08-23 10:35 on marvin
# -------------------------------------------------------------------

require_once("php/ConfigParser.php");
$config = new ConfigParser("../files/config.ini");

# Initiate ExerciseR Handler object. Does the
# login check.
require_once("php/ExerciseR.php");
$Handler = new ExerciseR($config);

# Check if we got a POST file name
if (empty($_POST["file"]))      { die("No file specified."); }
if (!is_string($_POST["file"])) { die("Input \"file\" is not a character string."); }

# FileHandler object handling the file download.
require_once("php/FileHandler.php");
$File = new FileHandler($config);

# From where to download. If not set, expect that we got the
# full path. Else we set up the path to the file based on the
# user session. Prevents to include full paths on the UI.
if (!isset($_POST["from"])) { $_POST["from"] = NULL; }

switch ($_POST["from"]) {
    # Load file from the exercise directory
    case "exercises":
        # Prevent that the user downloads the
        # solution and/or exercise configuration!
        $exclude = "/^exercise(\\.xml|\\.Rmd|_description\\.html|_solution\\.html|_tests\\.R|_tests\\.html)$/";
        if (preg_match($exclude, $_POST["file"])) {
            die("Stop, illegal download. You are not allowed to download this file.");
        }
        $file = sprintf("%s/%d/%s",
                        $config->get("path", "exercises"),
                        $_SESSION["exercise_id"], $_POST["file"]);
        break;
    # Load file from the user upload folder
    case "uploads":
        $file = sprintf("%s/user-%d/%s/%s",
                        $config->get("path", "uploads"),
                        $_SESSION["user_id"],
                        $_SESSION["exercise_hash"],
                        $_POST["file"]);
        break;
    # Default: expecting that $_POST["file"] contains full path
    default:
        $file = $_POST["file"];
}

if (!is_file($file)) {
    # TODO: do NOT show full path!
    die(sprintf("File \"%s\" does not exist (not found).", $file));
}
$File->getFile($file);

die();
