<?php
# -------------------------------------------------------------------
# - NAME:        FileHandler.php
# - AUTHOR:      Reto Stauffer
# - DATE:        2019-08-22
# -------------------------------------------------------------------
# - DESCRIPTION: Handling files (view file/download file)
#                to download/display files located in a folder not
#                accessible to the webserver.
# -------------------------------------------------------------------
# - EDITORIAL:   2019-08-22, RS: Created file on thinkreto.
# -------------------------------------------------------------------
# - L@ST MODIFIED: 2019-08-23 10:36 on marvin
# -------------------------------------------------------------------


class FileHandler {

    private $config = NULL;

    function __construct($config) {
        $this->config = $config;
    }
    

    public function getFile($file, $type = NULL) {

        if (!is_file($file)) { return(false); }

        // If type is NULL: guess Content-Type
        if (is_null($type)) {
            $check = preg_match("/[a-zA-Z]+$/", $file, $fileext);
            if (!$check) {
                die(sprintf("Problems guessing Content-Type from file name \"%s\"!",
                            $file));
            }
            // Getting application type/Content-Type from config object.
            $type = $this->config->get("content_type", strtolower($fileext[0]));
            $fileext = strtolower($fileext[0]);
        }

        header("Content-Type: " . $type);
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"" . basename($file) . "\""); 
        readfile($file);
        die();
    }
}


###require_once("ConfigParser.php");
###$CNF = new ConfigParser("../../files/config.ini");
###$x = new FileHandler($CNF);
###$x->getFile("../index.php");

