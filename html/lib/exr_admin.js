

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
$.fn.admin_table_groups = function(limit = null) {
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
        $("#admin-table-groups > table").DataTable();
    }
    // Calling getData
    args = {what: "groups"}
    if (limit != null) { args.limit = limit }
    $.fn.admin_getData(args, $(this), callback)
}

// --------------------------------------------
// Setting up users table
// --------------------------------------------
$.fn.admin_table_users = function(limit = null) {
    // Callback function, called by 'admin_getData'.
    callback = function(data, elem) {
        $(elem).html("<table class=\"table\"><thead><tr></thead></tr>"
            + "<tbody></tbody></table>")
        $(elem).find("> table > thead > tr")
            .append("<th class=\"ID center\">ID</th>")
            .append("<th class=\"name\">Name</th>")
            .append("<th class=\"groups\">Groups</th>")
        // Adding data
        var target = $(elem).find("tbody")
        $.each(data, function(key, val) {
            // Append new row, identify row, add class
            $(target).append("<tr></tr>");
            var tr = $(target).find("tr").last()
            $(tr).addClass(val.status);
            // Adding table cells
            $(tr).append("<td class=\"ID center\">" + val.user_id + "</td>")
            $(tr).append("<td class=\"name\">" + 
                         "<a class=\"btn btn-primary loginas\" href=\"../php/loginAs.php?user_id=" +
                         val.user_id + "\" target=\"_self\" " +
                         "data-toggle=\"tooltip\" title=\"Login as " + val.username + "\"></a>" +
                         val.username + "<br/>" +
                         "<span class=\"meta\">" + val.created + "</td>")
            if (val.groups == undefined) {
                $(tr).append("<td class=\"groups text-secondary\">no group membership</td>")
            } else {
                var tmp = []
                $.each(val.groups, function(k,v) { tmp.push("#" + v.name); });
                console.log(tmp)
                $(tr).append("<td class=\"groups\">" + tmp.join(", ") + "</td>");
            }
        });
        $("#admin-table-users > table").DataTable();
    }
    // Calling getData
    args = {what: "users"}
    if (limit != null) { args.limit = limit }
    $.fn.admin_getData(args, $(this), callback)

}


// --------------------------------------------
// Setting up exercise table
// --------------------------------------------
$.fn.admin_table_exercises = function(limit = null) {
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
        $("#admin-table-exercises > table").DataTable();
    }
    // Calling getData
    args = {what: "exercises"}
    if (limit != null) { args.limit = limit }
    $.fn.admin_getData(args, $(this), callback)
}


/* Processing the return of addUsers.php
 *
 * Parameters
 * ==========
 * elem : html object
 *      html element, where to add the output.
 * data : object
 *      object returned on success by addUsers.php (ajax call)
 */
$.fn.addUser_show_result = function(elem, data) {
    // Count number of errors
    var errors = 0
    $.each(data, function(key, val) {
        if (val.error != undefined) { errors += 1; }
    })

    // ----------------------------
    // No errors
    // ----------------------------
    if (errors == 0) {
        $(elem).attr("class", "alert alert-success")
               .html("<p>User-check ok</p>")
        $(elem).append("<form>" +
                       "<button type=\"button\" class=\"btn btn-primary\" " +
                       "id=\"admin-addusers-now\" name=\"submit\">Create new user(s) now</button>" +
                       "</form>")
        // Append table
        $(elem).append("<div class=\"table\"><table class=\"table\" /></div>");
        $(elem).find("table:first-child").append("<thead /><tbody />")
        // Adding content to table
        var head = $(elem).find("table:first-child > thead")
        var body = $(elem).find("table:first-child > tbody")
        $(head).append("<tr />")
        $(head).find("tr").append("<th>user</th>")
                          .append("<th>displayname</th>")
                          .append("<th>email</th>")
        $.each(data, function(username, rec) {
             if (rec.error == undefined) {
                 rec.error = ""; css_class = ""
             } else {
                css_class = "class=\"alert-danger\""
             }
             // Add new row to table
             $(body).append("<tr " + css_class + " />")
             var tr = $(body).find("tr:last-child")
             $(tr).append("<td class=\"username\">" + rec.username + "</td>")
                  .append("<td class=\"displayname\">" + rec.displayname + "</td>")
                  .append("<td class=\"email\">" + rec.email + "</td>")
        });

        // Add functionality
        $("#admin-addusers-now").on("click", function() {
            $.ajax("../php/addUsers.php", {
                data: {users: data},
                method: "POST",
                dataType: "JSON",
                success: function(data) {
                    if (data.error != undefined) {
                        css_class = "alert-danger";
                        message = data.error
                    } if (data.success != undefined) {
                        css_class = "alert-success";
                        message = data.success
                    } else {
                        css_class = "alert-danger";
                        message = "Got an unexpected return. Users added? Maybe!"
                    }
                    var elem = $("#admin-addusers-now").closest("div")
                    $(elem).attr("class", "alert " + css_class).html(message)
                    $.each($("#admin-add-users form input"), function(key, input) {
                        $(input).val("")
                    });
                    // Update "existing user" table
                    $("#admin-table-users").admin_table_users();
                }, error: function() {
                    alert("Whoops, something went wrong.")
                }
            });
        });

    // ----------------------------
    // Some errors occurred
    // ----------------------------
    } else {
        $(elem).attr("class", "alert alert-warning")
               .html("<p>User-check: " + errors + "/" +
                     Object.keys(data).length + " " +
                     "returned errors. Fix errors and try again.</p>")
        // Append table
        $(elem).append("<div class=\"table\"><table class=\"table\" /></div>");
        $(elem).find("table:first-child").append("<thead /><tbody />")
        // Adding content to table
        var head = $(elem).find("table:first-child > thead")
        var body = $(elem).find("table:first-child > tbody")
        $(head).append("<tr />")
        $(head).find("tr").append("<th>user</th>")
                          .append("<th>error</th>")
        $.each(data, function(username, rec) {
             if (rec.error == undefined) {
                 rec.error = ""; css_class = ""
             } else {
                css_class = "class=\"alert-danger\""
             }
             // Add new row to table
             $(body).append("<tr " + css_class + " />")
             var tr = $(body).find("tr:last-child")
             $(tr).append("<td><b>" + rec.username + "</b>: " +
                         rec.displayname + "<br/>" + rec.email + "</td>")
                  .append("<td class=\"error\">" + rec.error + "</td>")
        });
    }
} // End of $.fn.addUsers_show_result


/* Processing the return of addGroup.php
 *
 * Parameters
 * ==========
 * elem : html object
 *      html element, where to add the output.
 * data : object
 *      object returned on success by addGroup.php (ajax call)
 */
$.fn.addGroup_show_result = function(elem, data) {

    // ----------------------------
    // No errors
    // ----------------------------
    if (data.error == undefined) {
        $(elem).attr("class", "alert alert-success")
               .html("<p>Group-check ok</p>")
        $(elem).append("<form>" +
                       "<button type=\"button\" class=\"btn btn-primary\" " +
                       "id=\"admin-addgroup-now\" name=\"submit\">Create new group now</button>" +
                       "</form>")
        // Append table
        $(elem).append("<div class=\"table\"><table class=\"table\" /></div>");
        $(elem).find("table:first-child").append("<thead /><tbody />")
        // Adding content to table
        var head = $(elem).find("table:first-child > thead")
        var body = $(elem).find("table:first-child > tbody")
        $(head).append("<tr />")
        $(head).find("tr").append("<th>groupname</th>")
                          .append("<th>description</th>")

        // Only one entry
        if (data.error == undefined) {
            data.error = ""; css_class = ""
        } else {
           css_class = "class=\"alert-danger\""
        }
        // Add new row to table
        $(body).append("<tr " + css_class + " />")
        var tr = $(body).find("tr:last-child")
        $(tr).append("<td class=\"groupname\">" + data.groupname + "</td>")
             .append("<td class=\"description\">" + data.description + "</td>")
             .append("<td class=\"email\">" + data.email + "</td>")
        

        // Add functionality
        $("#admin-addgroup-now").on("click", function() {
            $.ajax("../php/addGroup.php", {
                data: {group: data},
                method: "POST",
                dataType: "JSON",
                success: function(data) {
                    if (data.error != undefined & data.groupname == undefined) {
                        css_class = "alert-danger";
                        message = data.error
                    } if (data.success != undefined) {
                        css_class = "alert-success";
                        message = data.success
                    } else {
                        css_class = "alert-danger";
                        message = "Got an unexpected return. Group added? Maybe!"
                    }
                    var elem = $("#admin-addgroup-now").closest("div")
                    $(elem).attr("class", "alert " + css_class).html(message)
                    $.each($("#admin-add-group form input"), function(key, input) {
                        $(input).val("")
                    });
                    // Update "existing user" table
                    $("#admin-table-groups").admin_table_groups();
                }, error: function() {
                    alert("Whoops, something went wrong.")
                }
            });
        });

    // ----------------------------
    // Some errors occurred
    // ----------------------------
    } else {
        $(elem).attr("class", "alert alert-warning")
               .html("<p>Group-check returned errors. Fix errors and try again.</p>")
        // Append table
        $(elem).append("<div class=\"table\"><table class=\"table\" /></div>");
        $(elem).find("table:first-child").append("<thead /><tbody />")
        // Adding content to table
        var head = $(elem).find("table:first-child > thead")
        var body = $(elem).find("table:first-child > tbody")
        $(head).append("<tr />")
        $(head).find("tr").append("<th>group</th>")
                          .append("<th>error</th>")

        // Add new row to table
        $(body).append("<tr class=\"alert-danger\" />")
        var tr = $(body).find("tr:last-child")
        $(tr).append("<td><b>" + data.groupname + "</b>: " +
                    data.description + "</td>")
             .append("<td class=\"error\">" + data.error + "</td>")
    }
} // End of $.fn.addUsers_show_result

// XML file upload when adding users
$(document).ready(function(){

    /* Functionality for "add new group"
     *
     * Adds the functionality to the form for "add new group"
     */
    $("#admin-add-group").on("click", "button.submit", function() {
        var form = $(this).closest("form")
        // Prevent default form submit
        $(form).submit(function(e) { return false; });
        
        // Loading form values.
        var groupname = $(form).find("input[name='groupname']").val()
        var description = $(form).find("input[name='description']").val()

        // Same limits as in the xml scheme addUsers.xsd
        if (groupname.length < 4 | groupname.length > 20) {
            alert("Group name must be 4 up to 50 characters long.")
        } else if (description.length < 5 | description.length > 50) {
            alert("Description must be 5 up to 300 characters long.")
        } else {
            // Element to show output/messages
            var elem = $(this).closest(".container").find(".admin-message > div")
            // If successfully uploaded: check "addUsers".
            $.ajax("../php/addGroup.php", {
                   data: {groupname: groupname, description: description},
                   method: "POST",
                   dataType: "JSON",
                   success: function(data) {
                       // Ups, error message
                       if (data.error != undefined & data.groupname == undefined) {
                           $(elem).attr("class", "alert alert-danger").html(data.error);
                       // Success message, add form to 'accept'
                       } else {
                           $.fn.addGroup_show_result(elem, data);
                       }
                   }, error: function() {
                       alert("Ups, something went wrong.")
                   }
            });
        }
    });

    /* Functionality for "add single user".
     *
     * Adds the functionality to the form for "add single user".
     */
    $("#admin-add-users").on("click", "button.submit", function() {
        var form = $(this).closest("form")
        // Prevent default form submit
        $(form).submit(function(e) { return false; });
        
        // Loading form values.
        var username = $(form).find("input[name='username']").val()
        var displayname = $(form).find("input[name='displayname']").val()
        var email = $(form).find("input[name='email']").val()

        // Helper function to check mail address.
        function isEmail(email) {
            var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            return regex.test(email);
        }
        // Same limits as in the xml scheme addUsers.xsd
        if (username.length < 4 | username.length > 20) {
            alert("Username must be 4 up to 20 characters long.")
        } else if (displayname.length < 5 | displayname.length > 50) {
            alert("Displayname must be 5 up to 50 characters long.")
        } else if (!isEmail(email)) {
            alert("Invalid email address.")
        } else {
            // Element to show output/messages
            var elem = $(this).closest(".container").find(".admin-message > div")
            // If successfully uploaded: check "addUsers".
            $.ajax("../php/addUsers.php", {
                   data: {username: username, displayname: displayname, email: email},
                   method: "POST",
                   dataType: "JSON",
                   success: function(data) {
                       // Ups, error message
                       if (data.error != undefined) {
                           $(elem).attr("class", "alert alert-danger").html(data.error);
                       // Success message, add form to 'accept'
                       } else {
                           $.fn.addUser_show_result(elem, data);
                       }
                   }, error: function() {
                       alert("Ups, something went wrong.")
                   }
            });
        }
    });

    /* Functionality for file upload, bulk upload for adding users.
     *
     * "Add user(s)" allows for bulk upload using an xml file. The
     * code below adds the interactive functionality.
     * Uses simpleUpload.js for file upload.
     */
    $("#admin-add-users input[type='file']").change(function(){
        //$(this).simpleUpload("php/upload.php", {
        $("#admin-add-users input[name='file']").simpleUpload("../php/upload.php", {
        allowedExts: ["xml"],
        start: function(file){
            //upload started
            $('#filename').html("Uploading " + file.name);
            $('#progress').html("Starting upload ...");
        },
        progress: function(progress){
            $('#progress').html("Progress: " + Math.round(progress) + "%");
        },
        success: function(data){
            // File upload successful
            $('#progress').html("Successfully uploaded file (" + data.file.tmp_name + ").");
            // Element to show output/messages
            var elem = $("#admin-add-users input[name='file']").closest(".container").find(".admin-message")
            // If successfully uploaded: check "addUsers".
            $.ajax("../php/addUsers.php", {
                   data: {xmlfile: data.destination},
                   method: "POST",
                   dataType: "JSON",
                   success: function(data) {
                       // Ups, error message
                       if (data.error != undefined) {
                           $(elem).attr("class", "alert alert-danger").html(data.error);
                       // Success message, add form to 'accept'
                       } else {
                           $.fn.addUser_show_result(elem, data);
                       }
                   },
                   error: function() {
                       alert("Error calling addUsers.php");
                   }
               }); 
                    
        },
        error: function(error){
            $('#progress').html("Failure!<br>" + error.name + ": " + error.message);
        }
        });
    });
});
