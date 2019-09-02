<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

// Loading configuration
$config = new ConfigParser("../files/config.ini");

# Loading the exercise class
$Handler = new ExerciseR($config);
$Handler->site_show_header();
?>
<a href="php/login_as.php?user_id=3">Login as user 3</a>

  <!-- CodeMirror -->
  <link rel="stylesheet" href="lib/codemirror.css">
  <link rel="stylesheet" href="lib/circle-progress.css">
  <script src="lib/opencpu-0.5.js"></script>
  <script src="lib/codemirror.js"></script>
  <script src="lib/r.js"></script>
  <script src="lib/simpleUpload-1.1.js"></script>
  <script src="lib/circle-progress.min.js"></script>
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){
        // CodeMirror options
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


        // ============================================================
        // Allow to download files
        // ============================================================
        // Takes a URL, param name, and data string
        // Sends to the server... The server can respond with binary data to download
        $.fn.download = function(url, postData) {
            // Build a form
            var form = $('<form></form>').attr('action', url).attr('method', 'post');
            // Add the one key/value
            $.each(postData, function(key, value) {
                form.append($("<input></input>").attr('type', 'hidden')
                    .attr('name', key).attr('value', value));
            });
            //send request
            form.appendTo('body').submit().remove();
        };
        $("#metainfo ul.exercise.files > li").on("click", function(x) {
            var file = $(this).html();
            $.fn.download("getFile.php", {from: "exercises", file: file});
        });


        // ============================================================
        // Output from opencpu calls
        // ============================================================
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

        $.fn.update_summary_ocpuoutput = function() {
            $.ajax({
                type: "POST", url: "getFile.php",
                data: {from: "uploads", file: "_ocpu_output.xml"},
                dataType: "xml",
                success: function(data) {
                    // Extract values
                    var tests_total  = parseInt($(data).find("tests").find("total")[0].textContent)
                    var tests_failed = parseInt($(data).find("tests").find("failed")[0].textContent)
                    var tests_passed = parseInt($(data).find("tests").find("passed")[0].textContent)
                    var rate = tests_passed / tests_total; //Math.round(tests_passed / tests_total, 2);
                    console.log("Ocpu summary: tests " + tests_total + ", " +
                                "passed: " + tests_passed + ", " +
                                "failed: " + tests_failed + ", rate: ", rate)

                    $("#ocpu_summary .round").circleProgress({value: rate});
                    $("#ocpu_summary span").html("Success: " + tests_passed + "/" + tests_total)
                }////,
                ////error: function(error){
                ////    alert("error loading opencpu output xml file");
                ////    console.log(error)
                ////    console.log(error.name + ": " + error.message);
                ////}
                ///// <<<==== if the file does not exist yet?
            });
        }

        // On initialization: try to update output (if files exixt)
        $.fn.update_tab_ocpuoutput();
        $.fn.update_summary_ocpuoutput();

        // Run script
        $("#btn-run").on("click", function() {
            var elem = $("#tab-ocpuoutput div.alert, #tab-ocpulog div.alert");
            elem.removeClass().addClass("alert alert-info")
                .html("<div class='spinner-border'></div> Running ...")
            $.ajax({
                url: "php/run.php",
                dataType: "JSON",
                success: function(data) {

                    // This is an interface issue, not a problem of the solution
                    if (data.error !== undefined) {
                        alert(data.error);
                    } else {
                        // If we got a "log" (custom log/erro message) jump to 
                        // opencpu log page.
                        if (data.log != undefined) {
                            $(elem).removeClass().addClass("alert")
                                .addClass("alert-danger")
                                .html("Something went wrong! Check logs for details.")
                            var tabid = "#tab-ocpulog"
                            data.console = data.log // copy
                        // If the returncode is 0: go to ocpuoutput
                        } else if (data.returncode == 0) {
                            //$("#tab-ocpulog .alert").removeClass().addClass("alert")
                            $(elem).removeClass().addClass("alert")
                                .addClass("alert-success")
                                .html("Test succesfully run, check output!");
                            var tabid = "#tab-ocpuoutput"
                        // Error: go to log page.
                        } else {
                            $(elem).removeClass().addClass("alert")
                                .addClass("alert-danger")
                                .html("Something went wrong! Check logs for details.")
                            var tabid = "#tab-ocpulog"
                        }

                        // Switch tab
                        $(".nav-tabs a[href=\"" + tabid + "\"]").tab("show");
                        $(".tab-pane.in.active").removeClass("in active");
    
    
                        // Remove CodeMirror output, re-create (had some
                        // problems when just changing content on inactive tabs)
                        $("#ocpulog").remove();
                        $(tabid).addClass("in active");
                        $("#tab-ocpulog").find(".CodeMirror").remove()
                        $("#tab-ocpulog").append("<textarea id=\"ocpulog\">"
                            + data.console + "</textarea>");
                        CodeMirror.fromTextArea(document.getElementById("ocpulog"), CMOpts);

                        // Update output
                        if (data.log == undefined) {
                            $.fn.update_tab_ocpuoutput();
                            $.fn.update_summary_ocpuoutput();
                        }
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

