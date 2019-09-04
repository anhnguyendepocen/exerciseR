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
$HandlerOptions = NULL;
// Loading the exercise class
$Handler = new ExerciseR($config, $HandlerOptions);
$Handler->site_show_header();
?>

  <!-- CodeMirror -->
  <script>
    $(document).ready(function(){
    });
  </script>

<?php $Handler->show_index(); ?>

<?php $Handler->site_show_footer(); ?>

