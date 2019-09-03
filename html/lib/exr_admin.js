

// --------------------------------------------
// Loading data from database
// 
// Parameters
// ==========
// data : object
//      At least requires an key:value pair what:"..."
//      used to handle the different requests by the php script.
// elem : object
//      html element on which the callback function should be
//      applied.
// callback : function
//      The function to be called after successfully loading the
//      data. The function is called with: "callback(data, elem)".
//
// Returns
// =======
// No return, adds data to the UI.
// --------------------------------------------
$.fn.admin_getData = function(data, elem, callback) {
    $.ajax("../php/getData.php", {
        method: "POST",
        dataType: "JSON",
        data: data,
        success: function(data) {
            console.log(data)
            callback(data, elem)
        },
        error: function() {
            alert("Error loading the data via getData.php")
        }
    }); 
}

// --------------------------------------------
// Setting up groups table
// --------------------------------------------
$.fn.admin_table_groups = function() {
    // Callback function, called by 'admin_getData'.
    callback = function(data, elem) {
        // Create table
        $(elem).html("<table class=\"table\"><thead><tr></thead></tr>"
            + "<tbody></tbody></table>")
        $(elem).find("> table > thead > tr")
            .append("<th class=\"ID center\">ID</th>")
            .append("<th class=\"name\">Name</th>")
            .append("<th class=\"description\">Description</th>")
            .append("<th class=\"created center\">Created</th>")
        // Adding data
        var target = $(elem).find("tbody")
        $.each(data, function(key, val) {
            // Append new row, identify row, add class
            $(target).append("<tr></tr>");
            var tr = $(target).find("tr").last()
            $(tr).addClass(val.status);
            // Adding table cells
            $(tr).append("<td class=\"ID center\">" + val.group_id + "</td>")
            $(tr).append("<td class=\"name\">" + val.groupname + "</td>")
            $(tr).append("<td class=\"description\">" + val.description + "</td>")
            $(tr).append("<td class=\"created\">" + val.created + "</td>")
        });
    }
    $.fn.admin_getData({what: "groups"}, $(this), callback)
}

// --------------------------------------------
// Setting up users table
// --------------------------------------------
$.fn.admin_table_users = function(data) {
    // Callback function, called by 'admin_getData'.
    callback = function(data, elem) {
        $(elem).html("<table class=\"table\"><thead><tr></thead></tr>"
            + "<tbody></tbody></table>")
        $(elem).find("> table > thead > tr")
            .append("<th class=\"ID center\">ID</th>")
            .append("<th class=\"name\">Name</th>")
            .append("<th class=\"created center\">Created</th>")
            .append("<th>&nbsp;</th>")
        // Adding data
        var target = $(elem).find("tbody")
        $.each(data, function(key, val) {
            // Append new row, identify row, add class
            $(target).append("<tr></tr>");
            var tr = $(target).find("tr").last()
            $(tr).addClass(val.status);
            // Adding table cells
            $(tr).append("<td class=\"ID\">" + val.user_id + "</td>")
            $(tr).append("<td class=\"name\">" + val.username + "</td>")
            $(tr).append("<td class=\"created center\">" + val.created + "</td>")
            $(tr).append("<td><a href=\"../php/loginAs.php?user_id="
                        + val.user_id + "\" target=\"_self\">Login as</a>");
        });
    }
    $.fn.admin_getData({what: "users"}, $(this), callback)
}


// --------------------------------------------
// Setting up exercise table
// --------------------------------------------
$.fn.admin_table_exercises = function(data) {
    // Callback function, called by 'admin_getData'.
    callback = function(data, elem) {
        $(elem).html("<table class=\"table\"><thead><tr></thead></tr>"
            + "<tbody></tbody></table>")
        $(elem).find("> table > thead > tr")
            .append("<th class=\"ID center\">ID</th>")
            .append("<th class=\"center\">assigned</th>")
            .append("<th class=\"name\">Name</th>")
            .append("<th class=\"description\">description</th>")
        // Adding data
        var target = $(elem).find("tbody")
        $.each(data, function(key, val) {
            // Append new row, identify row, add class
            $(target).append("<tr></tr>");
            var tr = $(target).find("tr").last()
            $(tr).addClass(val.status);
            // Adding table cells
            $(tr).append("<td class=\"ID center\">" + val.exercise_id + "</td>")
            $(tr).append("<td class=\"center\">"
                         + "<span class=\"exercise assigned\">" + val.count_assigned + "</span>/"
                         + "<span class=\"exercise retry\">"    + val.count_retry    + "</span>/"
                         + "<span class=\"exercise solved\">"   + val.count_solved   + "</span>/"
                         + "<span class=\"exercise closed\">"   + val.count_closed   + "</span></td>")
            $(tr).append("<td class=\"name\">" + val.name + "<br><span class=\"meta\">Created by: " +
                    val.created_by + ", " + val.created + "<span></td>")
            $(tr).append("<td class=\"description\">" + val.description + "</td>")
        });
    }
    $.fn.admin_getData({what: "exercises"}, $(this), callback)
}
