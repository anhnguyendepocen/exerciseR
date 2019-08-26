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


    /* Returns the property (setting) of a specific section in the config file.
     * 
     * Parameters
     * ==========
     * section : str
     *      Name of the section in the config file.
     * property : str
     *      Name of the setting/property which should be returned.
     * stop : bool
     *      If true (default) the function raises an error if the property
     *      cannot be found. If set to false, the function does not stop
     *      execution, but returns 'NULL' instead. In both cases an error
     *      will be thrown if the section cannot be found.
     *
     * Returns
     * =======
     * Returns the value of the section:property OR NULL (see input
     * argument 'stop') - or stops execution.
     */
    public function get($section, $property, $stop = true) {

        if (!in_array($section, array_keys($this->data))) {
            exit(sprintf("Cannot find config section \"%s\".", $section));
        }
        if (!in_array($property, array_keys($this->data[$section]))) {
            if (!$stop) { return(NULL); }
            exit(sprintf("Cannot find config property \"%s > %s\".", $section, $property));
        }
        return($this->data[$section][$property]);

    }

    function __toString() {

        $res = "Class \"" . get_class($this) . "\"\n-------------------------\n";
        foreach($this->data as $key=>$obj) {
            $tmp = array();
            foreach($obj as $property=>$value) {
                array_push($tmp, $property);
            }
            $res .= "[" . $key . "]:\n   " . join(", ", $tmp) . "\n";
        }
        return($res);

    }

}
