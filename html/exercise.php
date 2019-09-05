<?php
// Loading required config
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

// The "ExerciseR" class can be initialized with a set of options.
// We are using "js" and "css" here to add the required js libraries/scripts
// and the style sheets needed.
$HandlerOptions = array("js"=>array("lib/exr_opencpu.js",
                                    "lib/opencpu-0.5.js",
                                    "lib/codemirror.js",
                                    "lib/r.js",
                                    "lib/simpleUpload-1.1.js",
                                    "lib/circle-progress.min.js"),
                        "css"=>array("lib/codemirror.css",
                                     "lib/circle-progress.css"));
// Loading the exercise class
$Handler = new ExerciseR($config, true, $HandlerOptions);
$Handler->site_show_header();
?>

  <!-- CodeMirror -->
  <script>
    $(document).ready(function(){
        <?php
        // Public exercise?
        if (property_exists($Handler->_get, "public")) {
            $file = sprintf("%s/%s/_ocpu_output.html",
                            $config->get("path", "public"),
                            "foooooooobar.xml");
        } else {
            $file = sprintf("%s/user-%d/%s/_ocpu_output.html",
                            $config->get("path", "uploads"),
                            $_SESSION["user_id"], $_SESSION["exercise_hash"]);
        }
        ?>
        var xmlfile = "<?php print($file); ?>";
        $(document).exr_opencpu(xmlfile);
    });
  </script>

<?php $Handler->show_exercise(); ?>

<?php $Handler->site_show_footer(); ?>

