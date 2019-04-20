<?php

// Handling login and open database connection
require("DbHandler.php");
require("LoginHandler.php");

session_start();
$DbHandler = new DbHandler("test.db", "../");
if (!isset($_SESSION["exercise_hash"])) { die("Stop"); }


// Get session information
$exercise_hash = $_SESSION["exercise_hash"];
$exercise_id   = $_SESSION["exercise_id"];

// Update database
$res = $DbHandler->query(sprintf("SELECT run_counter FROM exercise_mapping "
                                  ."WHERE hash = '%s';", $exercise_hash))->fetchArray(SQLITE3_ASSOC);
$DbHandler->exec(sprintf("UPDATE exercise_mapping SET run_counter = %d, "
                        ."run_last = %d WHERE hash = '%s';",
                        (int)$res["run_counter"] + 1, time(), $exercise_hash));


// Array which will be returned
$res = array("hash" => $exercise_hash,
             "id"   => $exercise_id);

// Generate required paths
$volume  = sprintf("/voluem/%s", $exercise_hash);
$userdir = sprintf("uploads/user-%d/%s", $_SESSION["user_id"], $exercise_hash);
$cwd = getcwd();


// Check for some characters to avoid injections.
function prevent_injection($x) {
    $check =  preg_match("/\ |;|:|\n|\r|\t|'|\"/i", $x);
    if ($check != 0) { die(sprintf("ERROR: found suspicious characters (%s)!", $x)); }
}
prevent_injection($volume);
prevent_injection($userdir);
prevent_injection($cwd);
prevent_injection($exercise_hash);

// Create docker command
// Execution requires sudoers permissions, e.g.,
// add the following line to "/etc/sudoers".
// retos ALL=(ALL) NOPASSWD: /usr/bin/docker
$dockercmd = "sudo docker run -ti --rm -v \"%s/../exercises/%d\":/check "
            ."-v \"%s/../uploads/user-%d/%s\":/tocheck "
            ."-w /check -u docker r-base Rscript check.R";
$dockercmd = sprintf($dockercmd, $cwd, $exercise_id,
                     $cwd, $_SESSION["user_id"], $exercise_hash);
$res["cmd"] = $dockercmd;

// Calling docker, fetch return
ob_start();
#system("ls -l", $returnCode);
system($dockercmd, $returnCode);
$output = ob_get_clean();

// Update exercise data base status
$status = $returnCode == 0 ? 9 : 1;
$DbHandler->exec(sprintf("UPDATE exercise_mapping SET "
                        ."status = %d WHERE hash = '%s';",
                        (int)$status, $exercise_hash));

// Write return into log file
$fid = fopen(sprintf("%s/../%s/main.log", $cwd, $userdir), "w");
fwrite($fid, $output);
fclose($fid);

$DbHandler->close();

// Add information to the results array
$res["returncode"] = $returnCode;
$res["return"]     = $output;

print(json_encode($res)); die();

