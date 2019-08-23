<?php


class ConfigParser {

    # Used to store the configuration
    public $data = NULL;

    function __construct($file, $relpath = NULL) {

        if (!is_file($file)) {
            die(sprintf("Cannot find config file \"%s\".", $file));
        }

        # Read file
        $this->data = parse_ini_file($file, true);

        # If $relpath is specified: modify paths as defined
        # in the config file (e.g., when ConfigParser is used
        # in a subdirectory (php) instead of the root directory
        # of the UI/UX.
        if (!is_null($relpath)) {
            foreach($this->data["path"] as $key=>$val) {
                $this->data["path"][$key] = sprintf("%s/%s", $relpath, $val);
            }
            $this->data["sqlite3"]["dbfile"] = sprintf("%s/%s", $relpath,
                                                       $this->data["sqlite3"]["dbfile"]);
        }

    }

    public function get($section, $property) {

        if (!in_array($section, array_keys($this->data))) {
            exit(sprintf("Cannot find config section \"%s\".", $section));
        }
        if (!in_array($property, array_keys($this->data[$section]))) {
            exit(sprintf("Cannot find config property \"%s > %s\".", $section, $property));
        }
        return($this->data[$section][$property]);

    }

    function __toString() {

        $res = "";
        foreach($this->data as $key=>$obj) {
            $tmp = array();
            foreach($obj as $property=>$value) {
                array_push($tmp, $property);
            }
            $res .= $key . ":\n   " . join(", ", $tmp) . "\n";
        }
        return($res);

    }

}
