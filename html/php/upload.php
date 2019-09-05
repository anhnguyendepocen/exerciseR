<?php

session_start();

$sucess = false; // Default
if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
    // Use "upload_file_destination" stored in the current session.
    $destination = trim($_SESSION["upload_file_destination"]);
    if (!preg_match("/^\//", $destination)) {
        # TODO Why ../? Makes not too much sense.
        $destination = sprintf("../%s", $destination);
    }
    $sucess = move_uploaded_file($_FILES["file"]["tmp_name"], $destination);
    // Make a copy of the file
    $file_extension = pathinfo($destination, PATHINFO_EXTENSION);
    $dstcopy = preg_replace(sprintf("/.%s$/", $file_extension),
                            sprintf("_%s.%s", date("U"), $file_extension),
                            $destination);

    // Console log
    error_log(sprintf("Uploaded file to: " . $destination));
    if (!empty($_SESSION["upload_store_copy"])) {
        error_log($_SESSION["upload_store_copy"]);
        if ($_SESSION["upload_store_copy"]) {
            copy($destination, $dstcopy);
            error_log(sprintf("Copy ded file to: " . $dstcopy));
        }
    }
}
 
// $output will be converted into JSON 
if ($sucess) {
    $output = array("file" => $_FILES["file"],
                    "destination" => $destination,
                    "success" => true,
                    "message" => "Success!");
    $output["content"] = file_get_contents($destination);
} else {
    $output = array("success" => false, "error" => "Failure!");
}
 
if (!isset($_GET["_iframeUpload"])) { $_GET["_iframeUpload"] = 0; }
if (($iframeId = (int)$_GET["_iframeUpload"]) > 0) { //old browser... 
    header("Content-Type: text/html; charset=utf-8");
?> 
<!DOCTYPE html>
<html>
<head>
</head>
<body>
<script type="text/javascript">
var data = {
    id: <?php echo $iframeId; ?>,
    type: "json",
    data: <?php echo json_encode($output); ?>
};
parent.simpleUpload.iframeCallback(data);
</script> 
</body>
</html>
<?php
} else { //new browser... 
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($output);
}
 
?> 
