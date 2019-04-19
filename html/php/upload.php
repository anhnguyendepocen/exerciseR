<?php
/*
 * All of your application logic with $_FILES["file"] goes here.
 * It is important that nothing is outputted yet.
 */
if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
    error_log("-----------\n", 3, "log.txt");
    error_log(print_r($_FILES["file"]), 3, "log.txt");
    // basename() may prevent filesystem traversal attacks;
    // further validation/sanitation of the filename may be appropriate
    $success = move_uploaded_file($_FILES["file"]["tmp_name"], "foo.R");
    error_log((string)$success, 0);
} else {
    $success = false;
}

// $output will be converted into JSON
if ($success == 1) {
    $output = array("success" => true, "message" => "Success!");
} else {
    $output = array("success" => false, "error" => "Failure!");
}

header("Content-Type: application/json; charset=utf-8");
error_log(json_encode($output), 0);
echo json_encode($output);
?>
