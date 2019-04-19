<!DOCTYPE html>
<html lang="en">
<head>
<?php
# Loading the exercise class
require_once("php/DbHandler.php");
require_once("php/LoginHandler.php");
require_once("php/Exercise.php");

$DbHandler    = new DbHandler();
$LoginHandler = new LoginHandler($DbHandler);

# Loading exercise
$Exercise = new Exercise(1);

?>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
  <!-- jQuery library -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <!-- Latest compiled JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
  <!-- simple file uploader -->
  <script src="lib/simpleUpload.min.js"></script>
  <!-- CodeMirror -->
  <link rel="stylesheet" href="lib/codemirror.css">
  <script src="lib/codemirror.js"></script>
  <script src="lib/r.js"></script>
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){
        var textarea = document.getElementById("editor")
        console.log(textarea)
        var CMOpts = {lineNumbers: true, readOnly: true}
        var editor = CodeMirror.fromTextArea(textarea, CMOpts);

        $('input[type=file]').change(function(){
            $(this).simpleUpload("php/upload2.php", {
            allowedExts: ["R"],

            start: function(file){
                //upload started
                $('#filename').html(file.name);
                $('#progress').html("");
                $('#progressBar').width(0);
            },
            progress: function(progress){
                $('#progress').html("Progress: " + Math.round(progress) + "%");
                $('#progressBar').width(progress + "%");
            },
            success: function(data){
                $('#progress').html("Success!<br>Data: " + data.message); //JSON.stringify(data));
                editor.setValue(data.content)
            },
            error: function(error){
                $('#progress').html("Failure!<br>" + error.name + ": " + error.message);
            }
            });
        });
    });
  </script>
</head>
<body>

<div class="container">
  <?php $LoginHandler->logout_form(); ?>
  <h1>My First Bootstrap Page</h1>

  <div class="row">
    <div class="col-sm-6">
        <h2>Exercise</h2>
        <?php $Exercise->show(); ?>
    </div>
    <div class="col-sm-6">
        <h2>Upload File</h2>
        <div id="filename"></div>
        <div id="progress"></div>
        <div id="progressBar"></div>
        <input type="file" name="file">
    </div>
  </div>

  <div class="container">
    <h3>Your code/script</h3>
    This is the content of your current script file which will
    be executed/tested. You can update the file by uploading
    a new <i>R</i> script.
      <textarea id="editor">
# Some demo code
demo <- function(x) {
  print(x)
}
demo(matrix(1:10, ncol = 5))
     </textarea>
  </div>

</div>

</body>
</html>
