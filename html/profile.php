<?php
# Loading the exercise class
require_once("php/ExerciseR.php");
$Handler = new ExerciseR();
$Handler->site_show_header();
?>

    <div class="row">
      <div class="col-sm-12">
          <h1>Profile</h1>
          There would be space for user profile and stats.
      </div>
    </div>

<?php $Handler->site_show_footer(); ?>
