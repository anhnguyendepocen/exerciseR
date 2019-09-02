<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("../php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

# Loading required config
$config = new ConfigParser("../../files/config.ini", "..");

# Loading the exercise class
$Handler = new AdmineR($config, true);
$Handler->site_show_header();

# Used to load files from the "files" folder ont accessible
# by the webserver.
?>

  <!-- CodeMirror -->
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){
    });
  </script>

<?php $Handler->show_admin_index(); ?>

<?php $Handler->site_show_footer(); ?>

