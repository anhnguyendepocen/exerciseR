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

# -----------------------------------------------------------
# Helper functions
# -----------------------------------------------------------

/* Parse input arguments
 *
 * Returns
 * =======
 * A standard php object with the information 'who wants to execute what'.
 * Contains the exercise_id, user_id, and exercise_hash.
 */
function get_input_arguments($x) {
    # Check if 
    if (!isset($x["exercise_hash"])) {
        $msg = "exercise_hash not found in session information.";
        $msg .= join(", ", array_keys($x));
        print(json_encode(array("error" => $msg))); #"exercise_hash not found")));
        die(0);
    }
    $res = new StdClass();
    $res->exercise_id   = $x["exercise_id"];
    $res->user_id       = $x["user_id"];
    $res->exercise_hash = $x["exercise_hash"];
    return($res);
}

/* Update run counter
 *
 * Counts how often a user tries/tried to execute his/her solution.
 *
 * Parameters
 * ==========
 * db : DbHandler
 *      an exerciser DbHandler object (mysql handler).
 * inputs : object
 *      Object as returned by the function get_input_arguments.
 *      Contains exercise_id, user_id, and exercise_hash.
 */
function update_run_counter($db, $inputs) {
    if (!$db instanceof DbHandler) {
        throw new Exception("Input is not a DbHandler object.");
    }
    // Update database
    $res = $db->query(sprintf("SELECT run_counter FROM exercise_mapping "
                             ."WHERE hash = '%s';", $inputs->exercise_hash))->fetch_object();
    $sql = sprintf("UPDATE exercise_mapping SET run_counter = %d WHERE hash = '%s';",
                                     (int)$res->run_counter + 1,
                                     $inputs->exercise_hash);
    if (!$db->query($sql)) { throw new Exception("Problems updating run_counter."); }
    return;
}

/* Parsing opencpu xml result.
 *
 * Opencpu triggers a function from the exerciser R package which
 * creates an html output (knitr::spin output) and an xml file with
 * some information about the executed code. This function parses
 * the xml file and extracts some information.
 *
 * Parameter
 * =========
 * file : str
 *      path to the xml file to read.
 *
 * Returns
 * =======
 * Returns an object, the parses xml file.
 */
function load_summary_xml($file) {
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


# -----------------------------------------------------------
# Main part
# -----------------------------------------------------------

# TODO: pack the whole thing into a more structured form (some functions).
# and add some try/exceptions, maybe better return to allow the UI to show
# some error/warning messages to make the debugging simpler.

# Loading required config
$config = new ConfigParser("../../files/config.ini", "..");
# Loading the exercise class
$DbHandler = new DbHandler($config);

$LoginHandler = new LoginHandler($DbHandler);

// Get input arguments
$inputs = get_input_arguments($_SESSION);
//$inputs = (object)array("user_id"=>1, "exercise_id"=>1, "exercise_hash"=>"9e95606f89-1566843443-1");

// Update run_counter (how often the user tried to run his/her solution)
update_run_counter($DbHandler, $inputs);


// Initialize opencpu handler (send/fetch opencpu request)
$ocpu = new OpencpuHandler($config, $inputs->exercise_id,
                           $inputs->user_id, $inputs->exercise_hash);

// Load opencpu output (xml summary)
$xml = sprintf("%s/user-%d/%s/_ocpu_output.xml",
               $config->get("path", "uploads"),
               $inputs->user_id, $inputs->exercise_hash);
if (!is_file($xml)) {
    print json_encode(array("error" => sprintf("Cannot find output file \"%s\".",
                                               basename($xml))));
    die(0);
}
$xml = load_summary_xml($xml);

// If no tests failed, flag the test as 'solved'.
//$failed = (int)((array)$xml->failed)[0];
$status = (int)$xml->failed == 0 ? "solved" : "retry";
error_log(sprintf("Tests failed: %d (flag as \"%s\")",
                  (int)$xml->failed, $status));
$DbHandler->query(sprintf("UPDATE exercise_mapping SET "
                         ."status = '%s' WHERE hash = '%s';",
                          $status, $inputs->exercise_hash));

$DbHandler->close();

# -----------------------------------------------------------
# Create result/return array
# -----------------------------------------------------------


// Array which will be returned
$res = array("hash" => $inputs->exercise_hash,
             "id"   => $inputs->exercise_id);

// Add information to the results array
$res["returncode"] = $ocpu->returncode();
foreach($ocpu->get_result() as $key=>$val) { $res[$key] = $val; }

# Return json array, exit.
print(json_encode($res)); die(0);






