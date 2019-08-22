<?php
# Loading required config
require_once("php/ConfigParser.php");
$config = new ConfigParser("../files/config.ini");

# Loading the exercise class
require_once("php/ExerciseR.php");
$Handler = new ExerciseR($config);
$Handler->site_show_header();

# Used to load files from the "files" folder ont accessible
# by the webserver.
require_once("php/FileHandler.php");
?>

  <!-- CodeMirror -->
  <link rel="stylesheet" href="lib/codemirror.css">
  <script src="lib/opencpu-0.5.js"></script>
  <script src="lib/codemirror.js"></script>
  <script src="lib/r.js"></script>
  <script src="lib/simpleUpload-1.1.js"></script>
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){
        var CMOpts = {lineNumbers: true, readOnly: true}

        // Code styling/highlighting script tab
        var textarea = document.getElementById("script")
        var script   = CodeMirror.fromTextArea(textarea, CMOpts);
        // Code styling/highlighting log tab
        var textarea  = document.getElementById("ocpulog");
        var ocpulog = CodeMirror.fromTextArea(textarea, CMOpts);

        // File upload functionality
        $("input[type='file']").change(function(){
            //$(this).simpleUpload("php/upload.php", {
            $("input[name='file']").simpleUpload("php/upload.php", {
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
                $("#tab-script").find(".CodeMirror").remove()
                $("#tab-script").append("<textarea id=\"script\">" + data.content + "</textarea>");
                CodeMirror.fromTextArea(document.getElementById("script"), CMOpts);

                // Activate the "run button" if needed
                $("#btn-run.btn-secondary").prop("disabled", false)
                    .removeClass("btn-secondary").addClass("btn-success");
            },
            error: function(error){
                alert('x');
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
            if (id !== undefined) {
                // Regenerate CodeMirror
                $(href).find(".CodeMirror").remove()
                CodeMirror.fromTextArea(document.getElementById(id), CMOpts);
            }
        });
    
        $.fn.update_tab_ocpuoutput = function() {
            <?php
            $file = sprintf("%s/user-%d/%s/_ocpu_output.html",
                            $config->get("path", "uploads"),
                            $_SESSION["user_id"], $_SESSION["exercise_hash"]);
            ?>
            console.log("Loading file <?php print($file); ?>");
            $("#ocpuoutput").load("getFile.php", {file: "<?php print($file); ?>"});
            $.each($("#ocpuoutput").find("pre"), function() {
                console.log("xxx")
                if ($(this).find("code:contains(\"PASSED\")").length > 0) {
                    console.log(" -=------ PASSED")
                    $(this).css("background-color", "red")
                }
            });
        }

        // Run script
        $("#btn-run").on("click", function() {
            var elem = $("#tab-ocpuoutput div.alert, #tab-ocpulog div.alert");
            elem.removeClass().addClass("alert alert-info")
                .html("<div class='spinner-border'></div> Running ...")
            $.ajax({
                url: "php/run.php",
                dataType: "JSON",
                success: function(data) {

                    console.log("AJAX Response")
                    console.log(data)
                    console.log(data.cmd);
                    console.log(data.returncode + "  " + data.returnflag)
                    console.log(data)

                    // This is an interface issue, not a problem of the solution
                    if (data.error !== undefined) {
                        alert(data.error);
                    } else {
                        // If we got an error from R/ocpu: go to log page
                        if (data.returncode == 0) {
                            //$("#tab-ocpulog .alert").removeClass().addClass("alert")
                            $(elem).removeClass().addClass("alert")
                                .addClass("alert-success")
                                .html("Test succesfully run, check output!");
                            var tabid = "#tab-ocpuoutput"
                        // Else switch to output (user output)
                        } else {
                            //$("#tab-ocpulog .alert").removeClass().addClass("alert")
                            $(elem).removeClass().addClass("alert")
                                .addClass("alert-danger")
                                .html("Something went wrong! Check logs for details.")
                            var tabid = "#tab-ocplog"
                        }

                        // Switch tab
                        $(".nav-tabs a[href=\"" + tabid + "\"]").tab("show");
                        $(".tab-pane.in.active").removeClass("in active")
                        $(tabid).addClass("in active")
    
                        // Remove CodeMirror output, re-create (had some
                        // problems when just changing content on inactive tabs)
                        $("#ocpulog").remove();
                        $("#tab-ocpulog").find(".CodeMirror").remove()
                        $("#tab-ocpulog").append("<textarea id=\"ocpulog\">"
                            + data.console + "</textarea>");
                        CodeMirror.fromTextArea(document.getElementById("ocpulog"), CMOpts);
    
                        //ocpulog.setValue(data.return);
                        $.fn.update_tab_ocpuoutput();
                    }

                },
                error: function(xhr, status, error) {
                    //console.log(xhr.statusText)
                    alert("Error: " + error)
                }
            });
        });
    });
  </script>

<?php $Handler->show_content(); ?>

<?php $Handler->site_show_footer(); ?>

