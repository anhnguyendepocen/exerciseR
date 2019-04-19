<!DOCTYPE html>
<html lang="en">
<head>
<?php
# Loading the exercise class
require_once("php/ExercisoR.php");
$Handler = new ExercisoR();
?>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Latest compiled and minified CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
  <!-- jQuery library -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
  <!-- Latest compiled JavaScript -->
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
</head>
<body>

  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand" href="#">exercisoR</a>
      </div>
      <ul class="nav navbar-nav">
        <li><a href="index.php">Exercises</a></li>
        <li><a href="profile.php">Profile</a></li>
        <li class="active"><a href="about.php">About</a></li>
        <li><?php $Handler->LoginHandler->logout_form(); ?></li>
      </ul>
    </div>
  </nav>

  <div class="row">
    <div class="col-sm-12">
        <h1>Profile</h1>
        There would be space for user profile and stats.
    </div>
  </div>

</div>

</body>
</html>
