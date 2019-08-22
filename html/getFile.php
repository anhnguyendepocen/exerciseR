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
# - L@ST MODIFIED: 2014-09-13 15:12 on thinkreto
# -------------------------------------------------------------------

require_once("php/ConfigParser.php");
$config = new ConfigParser("../files/config.ini");

# Initiate ExerciseR Handler object. Does the
# login check.
require_once("php/ExerciseR.php");
$Handler = new ExerciseR($config);

# Check if we got a POST file name
if (empty($_POST["file"])) { die("No file specified."); }
if (!is_string($_POST["file"])) { die("Input \"file\" is not a character string."); }
if (!is_file($_POST["file"])) { die("File does not exist (not found)."); }

# FileHandler object handling the file download.
require_once("php/FileHandler.php");
$File = new FileHandler($config);
$File->getFile($_POST["file"]);

