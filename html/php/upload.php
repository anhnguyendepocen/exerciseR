<?php

session_start();

$sucess = false; // Default
if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
    // basename() may prevent filesystem traversal attacks;
    // further validation/sanitation of the filename may be appropriate
    //$destination = $_FILES["file"]["value"];
    $destination = trim($_SESSION["upload_file_destination"]);
    if (!preg_match("/^\//", $destination)) {
        # TODO Why ../? Makes not too much sense.
        $destination = sprintf("../%s", $destination);
    }
    $sucess = move_uploaded_file($_FILES["file"]["tmp_name"], $destination);
}
 
error_log(sprintf("Uploaded file to: " . $destination));

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
