<?php
/* Add new group to the ExerciseR
 *
 * This script is triggered via ajax/post request calls.
 * There are two "modes".
 *
 * Post request "add group"
 * - If the post request contains the three entries "groupname"
 *   and "description" the script assumes that you want
 *   to add a new group. Creates a stdClass object with
 *   this information. Check if the group already exist (same name).
 *   If so, an "error" message will be added to "$rval". This message
 *   is used by the UX to display errors.
 *
 * Add users:
 * - If the post request contains an object "group" the users will
 *   be added to the database. This mode should only be triggered
 *   by the UX!
 *
 */

function __autoload($name) {
    $file = sprintf("../php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

// Read exerciseR config file, connect to database,
// and check login. Administrator role required.
$config = new ConfigParser("../../files/config.ini", "..");
$DbHandler = new DbHandler($config);
$LoginHandler = new LoginHandler($DbHandler);
$UserClass = new UserClass($_SESSION["user_id"], $DbHandler);
if (!$UserClass->is_admin()) { $LoginHandler->access_denied("Admin permissions required."); }


// Getting post request input variables.
# Convert _POST to object.
# TODO: WARNING: needs to be POST!
$post = (object)$_POST;
if (empty($_POST)) { $post->what = "empty"; }

// Default return value (will be re-defined below)
$rval = array("error" => "Hoppala, something went wrong.");

/* Create group object.
 *
 * Small helper function which packs the information
 * received via _POST into a stdClass object.
 *
 * Parameters
 * ==========
 * x : stdClass object
 *      arguments (as object) as received via _POST.
 *
 * Returns
 * =======
 * Returns a stdClass object with user information.
 */
function create_group_object($x) {
    $rval = new stdClass();
    $rval->groupname   = $x->groupname;
    $rval->description = $x->description;
    return($rval);
}

/* Check if group exists.
 *
 * Based on input $x this function checks if the group exists.
 * If so (one or both) an "error" will be set on $x->$user->error.
 *
 * Parameters
 * ==========
 * x : stdClass object
 *      Object as returned by create_group_object.
 *
 * Return
 * ======
 * Returns a (possibly) modified version of $x. If a username or
 * email address is already in use, an error will be added to the
 * corresponding entry.
 */
function check_if_group_exists($x, $DbHandler) {
    $sql = "SELECT group_id FROM groups WHERE LOWER(%s) = LOWER(\"%s\");";
    $count = mysqli_num_rows($DbHandler->query(sprintf($sql, "groupname", $x->groupname)));
    if ($count) { $x->error = sprintf("Group \"%s\" exists.", $x->groupname); }
    return($x);
}


/* Add new group to database.
 *
 * Parameters
 * ==========
 * x : stdClass object
 *      Object as returned by create_group_object.
 *
 * Return
 * ======
 * In case we encounter errors while calling the database, an error
 * will be returned (array with an "error" message). Else, an array
 * with a "success" message is returned.
 */
function add_new_group_to_database($x, $DbHandler, $config) {

    // Prepare query/statement
    $query = "INSERT INTO groups (groupname, description) VALUES (?,?)";
    $prep_group = $DbHandler->prepare($query);
    if(!$prep_group) { die("Problems preparing the sql query"); }

    // Variable binding. Note that the variables do not yet exist (just binding)
    $prep_group->bind_param("ss", $groupname, $description);

    $DbHandler->query("START TRANSACTION");

    // Create the variables for the
    $groupname   = $x["groupname"];
    $description = $x["description"];
    $displayname = $x["displayname"];

    // Insert
    $prep_group->execute();
    $prep_group->close();
    $check = $DbHandler->query("COMMIT");

    if (!$check) {
        return(array("error"=>"Problems adding new group to database."));
    } else {
        return(array("success"=>"New group successfully added."));
    }
}

// ------------------------------------------------------------------
// Identify the "mode" in which this script is currently running.
// ------------------------------------------------------------------
if (property_exists($post, "group")) {
    $rval = add_new_group_to_database($post->group, $DbHandler, $config);
} else if (property_exists($post, "groupname") & property_exists($post, "description")) {
    // Generate object from _POST
    $rval = create_group_object($post);
} else {
    $rval = array("error" => "Cannot identify mode of current call to addGroup.php.");
}

// If we got errors: print and stop.
if (is_array($rval)) {
    print(json_encode($rval));
    die(0);
}

// Else check if the users already exist (modifies $rval)
$rval = check_if_group_exists($rval, $DbHandler);

$DbHandler->close();

print(json_encode($rval));


