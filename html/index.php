<!DOCTYPE html>
<html lang="en">
<head>
<?php
# Loading the exercise class
require_once("php/ExercisoR.php");

$Handler = new ExercisoR();

###require_once("php/DbHandler.php");
###require_once("php/LoginHandler.php");
###require_once("php/Exercise.php");
###
###$DbHandler    = new DbHandler();
###$LoginHandler = new LoginHandler($DbHandler);
###
#### Loading exercise
###$Exercise = new Exercise(1);

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
        var CMOpts = {lineNumbers: true, readOnly: true}

        // Code styling/highlighting script tab
        var textarea = document.getElementById("script")
        var script   = CodeMirror.fromTextArea(textarea, CMOpts);
        // Code styling/highlighting log tab
        var textarea  = document.getElementById("dockerlog");
        var dockerlog = CodeMirror.fromTextArea(textarea, CMOpts);

        // File upload functionality
        $('input[type=file]').change(function(){
            $(this).simpleUpload("php/upload.php", {
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
                script.setValue(data.content)
                $("#btn-run.btn-secondary").prop("disabled", false)
                    .removeClass("btn-secondary").addClass("btn-success");
            },
            error: function(error){
                $('#progress').html("Failure!<br>" + error.name + ": " + error.message);
            }
            });
        });

        // Run script
        $("#btn-run").on("click", function() {
            $.ajax({
                url: "php/run.php",
                dataType: "JSON",
                success: function(data) {
                    console.log(data.cmd);
                    if (data.returncode == 0) {
                        $("#logtab .alert").removeClass().addClass("alert")
                            .addClass("alert-success").html("Yey");
                    } else if (data.returncode == 1) {
                        $("#logtab .alert").removeClass().addClass("alert")
                            .addClass("alert-warning")
                            .html("The script runs well, but the result is not correct.")
                    } else {
                        $("#logtab .alert").removeClass().addClass("alert")
                            .addClass("alert-danger")
                            .html("Something went wrong! Check logs for details.")
                    }
                    $(".nav-tabs a[href=\"#logtab\"]").tab("show");
                    $(".tab-pane.in.active").removeClass("in active")
                    $("#logtab").addClass("in active")
                    dockerlog.setValue(data.return);

                },
                error: function(e) {
                    alert("whoops");
                }
            });
        });
    });
  </script>
</head>
<body>

  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand" href="#">exercisoR</a>
      </div>
      <ul class="nav navbar-nav">
        <li class="active"><a href="index.php">Exercises</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li><a href="about.php">About</a></li>
        <li><?php $Handler->LoginHandler->logout_form(); ?></li>
      </ul>
    </div>
  </nav>

  <?php $Handler->show_content(); ?>

</div>

</body>
</html>
