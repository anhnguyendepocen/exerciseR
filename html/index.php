<?php
# Loading the exercise class
require_once("php/ExerciseR.php");
$Handler = new ExerciseR();
$Handler->site_show_header();
?>

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
                $('#filename').html("Uploading " + file.name);
                $('#progress').html("Starting upload ...");
            },
            progress: function(progress){
                $('#progress').html("Progress: " + Math.round(progress) + "%");
            },
            success: function(data){
                $('#progress').html("Successfully uploaded file.");

                // Remove CodeMirror output, re-create (had some
                // problems when just changing content on inactive tabs)
                $("#script").remove();
                $("#scripttab").find(".CodeMirror").remove()
                $("#scripttab").append("<textarea id=\"script\">" + data.content + "</textarea>");
                CodeMirror.fromTextArea(document.getElementById("script"), CMOpts);

                // Activate the "run button" if needed
                $("#btn-run.btn-secondary").prop("disabled", false)
                    .removeClass("btn-secondary").addClass("btn-success");
            },
            error: function(error){
                $('#progress').html("Failure!<br>" + error.name + ": " + error.message);
            }
            });
        });

        // On change tabs: re-generate CodeMirror outut
        // which did not work properly when generated on inactive/hidden tabs.
        $("a[data-toggle='tab']").on("shown.bs.tab", function(e) {
            // Find tab id and textarea id
            var href = $(this).attr("href")
            var id = $(href).find("textarea").attr("id")
            $(href).find(".CodeMirror").remove()
            console.log("REMOVE")
            // Regenerate CodeMirror
            CodeMirror.fromTextArea(document.getElementById(id), CMOpts);
        });

        // Run script
        $("#btn-run").on("click", function() {
            var elem = $("#logtab div.alert");
            elem.removeClass().addClass("alert alert-info")
                .html("<div class='spinner-border'></div> Running ...")
            $.ajax({
                url: "php/run.php",
                dataType: "JSON",
                success: function(data) {
                    console.log(data.cmd);
                    console.log(data.returncode + "  " + data.returnflag)
                    if (data.returncode == 0) {
                        $("#logtab .alert").removeClass().addClass("alert")
                            .addClass("alert-success").html("Perfect, exercise successfully solved!");
                    } else {
                        $("#logtab .alert").removeClass().addClass("alert")
                            .addClass("alert-danger")
                            .html("Something went wrong! Check logs for details.")
                    }
                    $(".nav-tabs a[href=\"#logtab\"]").tab("show");
                    $(".tab-pane.in.active").removeClass("in active")
                    $("#logtab").addClass("in active")

                    // Remove CodeMirror output, re-create (had some
                    // problems when just changing content on inactive tabs)
                    $("#dockerlog").remove();
                    $("#logtab").find(".CodeMirror").remove()
                    $("#logtab").append("<textarea id=\"dockerlog\">" + data.return + "</textarea>");
                    CodeMirror.fromTextArea(document.getElementById("dockerlog"), CMOpts);

                    //dockerlog.setValue(data.return);

                },
                error: function(xhr, status, error) {
                    console.log(xhr.statusText)
                    alert("Error: " + error)
                }
            });
        });
    });
  </script>

<?php $Handler->show_content(); ?>

<?php $Handler->site_show_footer(); ?>

