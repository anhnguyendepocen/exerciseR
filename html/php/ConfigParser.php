<?php


class ConfigParser {

    # Used to store the configuration
    public $data = NULL;

    function __construct($file) {

        if (!is_file($file)) {
            die(sprintf("Cannot find config file \"%s\".", $file));
        }

        # Read file
        $this->data = parse_ini_file($file, true);

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
