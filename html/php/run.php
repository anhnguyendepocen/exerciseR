<?php

// Handling login and open database connection
require("DbHandler.php");
require("LoginHandler.php");
require("OcpuHandler.php");

session_start();
$DbHandler = new DbHandler("test.db", "../");
#if (!isset($_SESSION["exercise_hash"])) {
#    print(json_encode(array("error" => "exercise_hash not found")));
#    die(0);
#}
#$exercise_id   = $_SESSION["exercise_id"];
#$user_id       = $_SESSION["user_id"];
#$exercise_hash = $_SESSION["exercise_hash"];

$exercise_id   = 1;
$user_id       = 2;
$exercise_hash = "7029e93faa-1555679436-2";

// Update database
$res = $DbHandler->query(sprintf("SELECT run_counter FROM exercise_mapping "
                                  ."WHERE hash = '%s';", $exercise_hash))->fetchArray(SQLITE3_ASSOC);
$DbHandler->exec(sprintf("UPDATE exercise_mapping SET run_counter = %d, "
                        ."run_last = %d WHERE hash = '%s';",
                        (int)$res["run_counter"] + 1, time(), $exercise_hash));


// Array which will be returned
$res = array("hash" => $exercise_hash, "id" => $exercise_id);

// Generate required paths
$cwd = getcwd();
$dir          = sprintf("%s/../../files", $cwd);
#$user_dir     = sprintf("%s/../uploads/user-%d/%s", $cwd, $user_id, $exercise_hash);
#$exercise_dir = sprintf("%s/../exercises", $cwd);


// Some logging
$fid = fopen(sprintf("%s/main.log", $dir), "w");
fwrite($fid, "Calling opencpu");
fclose($fid);
$log = fopen("test.log", "w");
fwrite($log, "Calling opencpu\n");

$ocpu = new OcpuHandler($dir, $exercise_id, $user_id, $exercise_hash);

fwrite($log, "Object initialized\n");
fwrite($log, json_encode($ocpu->get_result()));
fclose($log);


// Update exercise data base status
$status = $ocpu->has_result() ? 1 : 9;
$DbHandler->exec(sprintf("UPDATE exercise_mapping SET "
                        ."status = %d WHERE hash = '%s';",
                        (int)$status, $exercise_hash));

$DbHandler->close();

// Add information to the results array
####print("----------------------------------\n");
$res["returncode"] = in_array("error", array_keys($ocpu->get_result())) ? 9 : 0;
foreach($ocpu->get_result() as $key=>$val) { $res[$key] = $val; }

###print("----------------------------------\n");
###print($res["console"]);
###print("----------------------------------\n");

print(json_encode($res)); die();

