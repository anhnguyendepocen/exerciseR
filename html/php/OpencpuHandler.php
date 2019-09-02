<?php

class OpencpuHandler {


    private $server_url  = "localhost:5656";
    // The function to be called
    //private $server_path = "ocpu/library";
    private $server_fun  = "ocpu/library/exerciser/R/check_exercise";
    private $result = NULL;
    private $_returncode = -9;

    private $exercise_dir = NULL;

    function __construct($config, $exercise_id, $user_id, $exercise_hash) {

        if (!is_dir($config->get("path", "exercises"))) {
            $this->result = array("error" => sprintf("Exercise directory \"%s\" not found",
                                                     $config->get("path", "exercises")));
            return;
        }
        if (!is_dir($config->get("path", "uploads"))) {
            $this->result = array("error" => sprintf("Uploads directory \"%s\" not found",
                                                     $config->get("path", "uploads")));
            return;
        }

        # Fetching hash
        $ret = $this->_curl_exec_test($config->get("path", "files"),
                                      (int)$exercise_id, (int)$user_id, $exercise_hash);
        if (empty($ret)) {
            error_log("[error] No response from opencpu - service offline?", 0);
            $this->result = array("error" => "No response from opencpu: service not running.");
            return;
        } else if (!$ret) {
            error_log("[error] Did not get opencpu execution hash!", 0);
            $this->result = array("error" => "Problems connecting opencpu/get opencpu hash.");
            return;
        }

        # If this worked: extract hash
        $ocpu = $this->_extract_hash($ret);
        if (!$ocpu) {
            error_log("[error] Cannot extract hash, check function failed. Return error message.");
            $this->result = array("log" => $ret);
            $this->_returncode = 999;
            return;
        }

        # Else: all fine. Loading console output
        $this->result = $this->_curl_get_return($ocpu->path, $ocpu->hash, "console/text");
        $this->_returncode = 0;

    }

    public function returncode() { return($this->_returncode); }

    public function has_result() {
        // no data
        if (is_null($this->result)) {
            return(false);
        // fetched error message
        } else if (in_array("error", array_keys($this->result))) {
            return(false);
        } else {
            return(true);
        }
    }

    public function get_result() {
        if (!is_null($this->result)) {
            return($this->result);
        } else {
            return(array("error" => "No information loaded so far."));
        }
    }

    public function show() {
        print(json_encode($this->get_result()));
    }

    private function _curl_exec_test($dir, $exercise_id, $user_id, $exercise_hash) {

        # OpenCPU url
        $url        = sprintf("%s/%s", $this->server_url, $this->server_fun);

        # Curl data sent (POST)
        $curl_data  = sprintf("dir=\"%s/%s\"", getcwd(), $dir);       # where we have the file
        $curl_data .= sprintf("&exercise_id=%d",       $exercise_id);   # Exercise ID
        $curl_data .= sprintf("&user_id=%d",           $user_id);       # Obviously the user ID
        $curl_data .= sprintf("&exercise_hash=\"%s\"", $exercise_hash); # User exercise hash

        $curl_args = array(CURLOPT_URL            => $url,
                           CURLOPT_CUSTOMREQUEST  => "POST",
                           CURLOPT_RETURNTRANSFER => true,
                           CURLOPT_POST           => true,
                           CURLOPT_POSTFIELDS     => $curl_data);

        error_log("============= curl call ==============");
        foreach($curl_args as $key=>$val) {
            error_log(sprintf("[curl %s]: %s", $key, $val));
        }
        // Use CURL to run the script.
        $curl = curl_init();
        curl_setopt_array($curl, $curl_args);
        $response = curl_exec($curl);
        curl_close($curl);
        if (!empty($response)) { foreach(explode("\n", $response) as $line) { error_log($line); } }
        error_log("=========== end curl call ============");
        return($response);
    }

    private function _extract_hash($ret) {
        // Extract temporary hash
        preg_match("/(ocpu\/tmp)\/([\w]+)\/console/", $ret, $matches); 
        if (count($matches) != 3) {
            return(false);
        } else {
            $tmp = new stdClass();
            $tmp->path = $matches[1];
            $tmp->hash = $matches[2];
            return($tmp);
        }
    }


    private function _curl_get_return($path, $hash, $what) {
    // -----------------------------------------------
    // Fetch console output
        $url = sprintf("%s/%s/%s/%s", $this->server_url, $path, $hash, $what);
        ##print("\n\n .........................................\n");
        ##print_r("\n\n".$url."\n\n");
        $curl_args = array(CURLOPT_URL => $url,
                           CURLOPT_CUSTOMREQUEST => "POST",
                           CURLOPT_RETURNTRANSFER => true);
        $curl = curl_init();
        curl_setopt_array($curl, $curl_args);
        $response = curl_exec($curl);
        ##print_r($response);
        curl_close($curl);
        return(array("console" => $response));
    }

}

//$obj = new OcpuHandler();
//$obj->show();



