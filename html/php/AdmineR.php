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
    <script>
    $(document).ready(function() {
        $.fn.getData = function(data, callback) {
            $.ajax("getData.php", {
                url: "getData.php",
                method: "POST",
                dataType: "JSON",
                data: data,
                success: function(data) {
                    callback(data)
                    if (data.length == 0) {
                        $(elem).html("No data ...")
                        return;
                    }
                    //var elem = $("#admin-exercises");
                    //// Create table
                    //$(elem).html("<table></table>");
                    //$(elem).append("<thead><tr></tr></thead><tbody></tbody>");
                    //// Adding header
                    //$.each(data[1], function(key, val) {
                    //    $(elem).find("thead > tr").append("<th class=\"" + key + "\">"
                    //                                      + key + "</th>")
                    //});
                    //$.each(data, function(key, val) {
                    //    $(elem).find("tbody").append("<tr></tr>");
                    //    $.each(val, function(k, v) {
                    //        $(elem).find("tbody > tr:last-child")
                    //            .append("<td>" + v + "</td>")
                    //    });
                    //    console.log(key)
                    //});
                }
            }); 
        }
    });
    </script>
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

    /* Show Admin Index Page */
    public function show_admin_index() {

        // Open bootstrap container
        ?> 
        <script>
        $(document).ready(function() {
            // --------------------------------------------
            // Setting up groups table
            // --------------------------------------------
            $.fn.index_table_groups = function(data) {
                // Create table
                var target = $("#index-table-groups")
                $(target).html("<table class=\"table\"><thead><tr></thead></tr>"
                    + "<tbody></tbody></table>")
                $(target).find("> table > thead > tr")
                    .append("<th>ID</th>")
                    .append("<th>Name</th>")
                    .append("<th>Created</th>")
                var target = $(target).find("tbody")
                $.each(data, function(key, val) {
                    console.log(key)
                    $(target).append("<tr></tr>")
                    $(target).first("tr").append("<td>" + val.group_id + "</td>")
                    $(target).first("tr").append("<td>" + val.groupname + "</td>")
                    $(target).first("tr").append("<td>" + val.created + "</td>")
                });
            }
            // Load data and create table
            $.fn.getData({what: "groups", limit: 10}, $.fn.index_table_groups);

            // --------------------------------------------
            // Setting up users table
            // --------------------------------------------
            $.fn.index_table_users = function(data) {
                // Create table
                var target = $("#index-table-users")
                $(target).html("<table class=\"table\"><thead><tr></thead></tr>"
                    + "<tbody></tbody></table>")
                $(target).find("> table > thead > tr")
                    .append("<th>ID</th>")
                    .append("<th>Name</th>")
                    .append("<th></th>")
                var target = $(target).find("tbody")
                $.each(data, function(key, val) {
                    console.log(key)
                    $(target).append("<tr></tr>")
                    $(target).first("tr").append("<td>" + val.user_id + "</td>")
                    $(target).first("tr").append("<td>" + val.username + "</td>")
                    $(target).first("tr").append("<td><a href=\"login_as.php?user_id="
                                + val.user_id + "\" target=\"_self\">Login as</a>");
                });
            }
            // Load data and create table
            $.fn.getData({what: "users", limit: 10}, $.fn.index_table_users);
        });
        </script>

        <div class="container">
            <h3>Groups</h3>
            <div class="table-responsive" id="index-table-groups"></div>
            
            <h3>Exercises</h3>
            <div class="table-responsive" id="index-table-exercises"></div>

            <h3>Users</h3>
            <div class="table-responsive" id="index-table-users"></div>
        </div>
        <?php

    }

    public function show_admin_exercises() {

        // Open bootstrap container
        ?> 
        <script>
        $(document).ready(function() {
            var groups = $.fn.getData({what: "groups", limit: 10});
            console.log(groups)
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
    

