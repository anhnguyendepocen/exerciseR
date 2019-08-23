<?php
# Loading required config
require_once("../php/ConfigParser.php");
$config = new ConfigParser("../../files/config.ini", "..");

# Loading the exercise class
require_once("../php/ExerciseR.php");
require_once("../php/AdmineR.php");
$Handler = new AdmineR($config, true);
$Handler->site_show_header();

# Used to load files from the "files" folder ont accessible
# by the webserver.
require_once("../php/FileHandler.php");
?>

  <!-- CodeMirror -->
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){
    });
  </script>

<?php $Handler->show_admin_exercises(); ?>

<?php $Handler->site_show_footer(); ?>

