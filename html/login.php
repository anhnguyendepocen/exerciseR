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
$Handler = new ExerciseR($config, true, $HandlerOptions);
$Handler->site_show_header();
?>

  <!-- CodeMirror -->
    <script>
        $(document).ready(function(){
        });
    </script>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1>ExerciseR login</h1>
                <p>
                Login to the exercixeR (currently experimental).
                You don't have an account yet? Well, sorry!
                Registration currently not implemented (#TODO).
                </p>
                <form method="POST">
                <input type="text" name="username" /><br />
                <input type="password" name="password" /><br />
                <input type="submit" value="Login" name="submit" /><br />
                </form>
            </div>
        </div>
    </div>

<?php $Handler->site_show_footer(); ?>

