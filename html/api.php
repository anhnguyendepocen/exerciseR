<?php
# Loading the exercise class
require_once("php/ExerciseR.php");
$Handler = new ExerciseR();
$Handler->site_show_header();
?>

  <!-- CodeMirror -->
  <link rel="stylesheet" href="lib/codemirror.css">
  <script src="lib/opencpu-0.5.js"></script>
  <script src="lib/codemirror.js"></script>
  <script src="lib/r.js"></script>
  <script>
    // http://simpleupload.michaelcbrook.com/
    $(document).ready(function(){

        ocpu.seturl("//localhost/ocpu/library/exerciser/R")
        $.init = ocpu.call("mean", {"x": 1}, function(session){
            console.log(session)
            $.session = session
        });
        console.log($.init)

    });
  </script>

</head>
<body>
<div class="content">Content ...</div>
</body>


