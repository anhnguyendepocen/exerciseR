<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

# TODO: pack the whole thing into a more structured form (some functions).
# and add some try/exceptions, maybe better return to allow the UI to show
# some error/warning messages to make the debugging simpler.

# Loading required config
$config = new ConfigParser("../../files/config.ini", "..");
# Loading the exercise class
$Handler = new ExerciseR($config);

# Check if 
if (!isset($_SESSION["exercise_hash"])) {
    print(json_encode(array("error" => "exercise_hash not found")));
    die(0);
}
$exercise_id   = $_SESSION["exercise_id"];
$user_id       = $_SESSION["user_id"];
$exercise_hash = $_SESSION["exercise_hash"];

#$exercise_id   = 1;
#$user_id       = 2;
#$exercise_hash = "7029e93faa-1555679436-2";

// Update database
$res = $Handler->DbHandler->query(sprintf("SELECT run_counter FROM exercise_mapping "
                                  ."WHERE hash = '%s';", $exercise_hash))->fetch_object();
$sql = sprintf("UPDATE exercise_mapping SET run_counter = %d WHERE hash = '%s';",
                                 (int)$res->run_counter + 1,
                                 $exercise_hash);
if (!$Handler->DbHandler->query($sql)) {
    throw new Exception("Problems updating run_counter.");
}


// Array which will be returned
$res = array("hash" => $exercise_hash, "id" => $exercise_id);

// Some logging
$fid = fopen(sprintf("%s/main.log", $config->get("path", "files")), "w");
fwrite($fid, "Calling opencpu");
fclose($fid);
$log = fopen("test.log", "w");
fwrite($log, "Calling opencpu\n");

// Handling login and open database connection
require("OcpuHandler.php");
$ocpu = new OcpuHandler($config, $exercise_id, $user_id, $exercise_hash);

fwrite($log, "Object initialized\n");
fwrite($log, json_encode($ocpu->get_result()));
fclose($log);

// Check success rate of the submission.
function _load_summary_xml($file) {
    if (!is_file($file)) {
        die(sprintf("Cannot find requested file \"%s\".", $file));
    }
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file);
    if ($xml === false) {
        echo "Failed loading XML: ";
        foreach(libxml_get_errors() as $error) {
            echo "<br>", $error->message;
        }
        die(0);
    }
    return($xml);
}

// Load opencpu output (xml summary)
$xml = sprintf("%s/user-%d/%s/_ocpu_output.xml",
               $config->get("path", "uploads"),
               $user_id, $exercise_hash);
$xml = _load_summary_xml($xml);

$failed = (int)((array)$xml->failed)[0];
$status = $failed == 0 ? 9 : 1;
$Handler->DbHandler->query(sprintf("UPDATE exercise_mapping SET "
                                 ."status = %d WHERE hash = '%s';",
                                 (int)$status, $exercise_hash));

$Handler->DbHandler->close();

// Add information to the results array
####print("----------------------------------\n");
$res["returncode"] = in_array("error", array_keys($ocpu->get_result())) ? 9 : 0;
foreach($ocpu->get_result() as $key=>$val) { $res[$key] = $val; }

###print("----------------------------------\n");
###print($res["console"]);
###print("----------------------------------\n");

print(json_encode($res)); die();

