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

    <div class="row">
      <div class="col-sm-12">
          <h1>Profile</h1>
          There would be space for user profile and stats.
      </div>
    </div>

<?php $Handler->site_show_footer(); ?>
