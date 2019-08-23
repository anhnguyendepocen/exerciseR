<?php

/* Main handling class for the exerciseR interface.
 */
class AdmineR extends ExerciseR {

    // All admin pages need to have "true".
    // Used to check if the current user has enough privileges
    // to see this page.
    private $admin_page = true;

    /* Overruling UI header
     */
    public function site_show_header() {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>exerciseR</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/exerciseR.css">
    <script src="../lib/jquery-3.4.1.min.js"></script>
    <script src="../lib/bootstrap-4.2.1.min.js"></script>
</head>
<body>

    <nav id="top-nav" class="navbar navbar-expand-sm bg-secondary navbar-light">
        <img id="exerciserlogo" src="../css/logo.svg"></img>
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link text-light" href="users.php">users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-light" href="../">back</a>
            </li>
            <li class="nav-item">
                <?php $this->LoginHandler->logout_form($this->UserClass); ?>
            </li>
        </ul>
    </nav>

    <!-- content container -->
    <div class="container">

        <?php
    }

    public function show_admin_index() {

        // Open bootstrap container
        ?> 
        <div class="container">
        Admin ...
        </div>
        <?php

    }

    public function show_admin_exercises() {

        // Open bootstrap container
        ?> 
        <script>
        $(document).ready(function() {
            $.fn.getData = function(data) {
                $.ajax("getData.php", {
                    url: "getData.php",
                    method: "POST",
                    dataType: "JSON",
                    data: data,
                    success: function(data) {
                        var elem = $("#admin-exercises");
                        if (data.length == 0) {
                            $(elem).html("No data ...")
                            return;
                        }
                        // Create table
                        $(elem).html("<table></table>");
                        $(elem).append("<thead><tr></tr></thead><tbody></tbody>");
                        // Adding header
                        $.each(data[1], function(key, val) {
                            $(elem).find("thead > tr").append("<th class=\"" + key + "\">"
                                                              + key + "</th>")
                        });
                        $.each(data, function(key, val) {
                            $(elem).find("tbody").append("<tr></tr>");
                            $.each(val, function(k, v) {
                                $(elem).find("tbody > tr:last-child")
                                    .append("<td>" + v + "</td>")
                            });
                            console.log(key)
                        });
                    }
                }); 
            }
            $.fn.getData({what: "exercises", limit: 10});

        });
        </script>
        <div class="container">
        Exercises ...

        <div id="admin-exercises"></div>
        </div>
        <?php

    }

    public function show_admin_users() {

        // Open bootstrap container
        ?> 
        <div class="container">
        User list
        </div>
        <?php

    }


}
    

