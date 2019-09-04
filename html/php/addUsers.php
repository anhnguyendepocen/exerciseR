<?php
/* Add new users to the ExerciseR
 *
 * This script is triggered via ajax/post request calls.
 * There are basically three modes.
 *
 * Post request "add single user":
 * - If the post request contains the three entries "username",
 *   "displayname" and "email" the script assumes that you want
 *   to add a single user. Creates a stdClass object with
 *   this information.
 *   Afterwards, check if the user(s) already exist (see below).
 *
 * Post request contains 'xmlfile':
 * - Validate the xml file using the xmlscheme "addUser.xsd". If valid,
 *   the xml file will be read and a stdClass object is 
 *   created containing username, displayname, and email address.
 *   In case the xml file is not valid, the errors will be added
 *   to "$rval". Used by the UX to display errors.
 *   Afterwards, check if the user(s) already exist (see below).
 *
 * Check if the user(s) exist:
 * - In case the script has been started in 'xmlfile' mode or
 *   "add single user": check if the users already exist (if a user
 *   with this username or email address is already registered).
 *   If so, an "error" message will be added to "$rval". This message
 *   is used by the UX to display errors.
 *
 * Add users:
 * - If the post request contains an object "users" the users will
 *   be added to the database. This mode should only be triggered
 *   by the UX! The procedure: this scirpt is first called in
 *   'xmlfile' mode or "add single user", if everything works
 *   fine the script returns an object with the information about
 *   the users which should be added. If there are no errors, the
 *   frontend shows a "Create new users now" button which sends
 *   the information on _POST["users"] back to this script which
 *   will then add the users to the database.
 *
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

function parse_xml_file($file, $xsd = "addUsers.xsd") {
    // Wrong input
    if (!is_string($file)) { return(array("error" => "Input \"file\" must be a string.")); }

    // File does not exist: return an error.
    if (!is_file($file)) { return(array("error" => sprintf("Cannot find \"%s\"", $file))); }
    if (!is_file($xsd))  { return(array("error" => sprintf("Cannot find xsd file \"%s\".", $xsd))); }

    // Parse xml file
    libxml_use_internal_errors(true);
    $xml = new XMLReader;
    $xml->open($file);
    $xml->setSchema($xsd);
    // Read file 
    while (@$xml->read()) {};

    // If validation returned errors:
    if (count(libxml_get_errors()) > 0) {
        $msg = "<p><b>XML validation error</b></p>\n";
        foreach (libxml_get_errors() as $error) {
            $msg .= sprintf("[error] %s<br>\n", $error->message);
        }
        return(array("error" => $msg));
    }

    // Read xml file
    $doc = new DOMDocument;
    // If there are any problems reading the file: drop errors.
    // This should not happen as we already validated our xml file.
    if (!$doc->load($file)) {
        $msg = "<p><b>Read XML error</b></p>\n";
        foreach (libxml_get_errors() as $error) {
            $msg .= sprintf("[error] %s<br>\n", $error->message);
        }
        return(array("error" => $msg));
    }

    // Extract 'user' definition(s)
    $data = new stdClass();
    foreach ($doc->getElementsByTagName("user") as $rec) {
        $username     = $rec->getElementsByTagName("username")[0]->textContent;
        $displayname  = $rec->getElementsByTagName("displayname")[0]->textContent;
        $email        = $rec->getElementsByTagName("email")[0]->textContent;
        $tmp = array("username" => $username, "displayname" => $displayname, "email" => $email);
        $data->$username = (object)$tmp;
        unset($tmp);
    }
    return($data);
} // End of function parse_xml_file

/* Helper function to check if we are in the "add single user" mode.
 *
 * Parameters
 * ==========
 * x : object
 *      arguments passed over via _POST
 * check : array
 *      an array of strings with the "key names" of the elements
 *      which have to be available in $x to identify the call as
 *      "add single user" mode.
 *
 * Returns
 * =======
 * Returns boolean true if all elements in $check exist in $x.
 * Else boolean false is returned.
 */
function check_mode_single_user($x, $check = array("username", "displayname", "email")) {
    foreach($check as $c) {
        if (!property_exists($x, $c)) { return(false); }
    }
    return(true);
}

/* Parsing single-user.
 *
 * If this script has been started in "add single user" mode
 * we have the config we got the user settings via _POST.
 * This function creates a stdClass object and returns it.
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
function parse_single_user($x) {
    $rval = new stdClass();
    $user = $x->username;
    $rval->$user = new stdClass();
    $rval->$user->username    = $x->username;
    $rval->$user->displayname = $x->displayname;
    $rval->$user->email       = $x->email;
    return($rval);
}

/* Check if user exists.
 *
 * Based on input $x this function checks if the username or email
 * address is already registered. If so (one or both) an "error"
 * will be set on $x->$user->error.
 *
 * Parameters
 * ==========
 * x : stdClass object
 *      Object as returned by parse_xml_file or parse_single_user.
 *
 * Return
 * ======
 * Returns a (possibly) modified version of $x. If a username or
 * email address is already in use, an error will be added to the
 * corresponding entry.
 */
function check_if_user_or_mail_exists($x, $DbHandler) {
    $sql = "SELECT user_id FROM users WHERE LOWER(%s) = LOWER(\"%s\");";
    foreach ($x as $username=>$rec) {
        $count_user = mysqli_num_rows($DbHandler->query(sprintf($sql, "username", $rec->username)));
        $count_mail = mysqli_num_rows($DbHandler->query(sprintf($sql, "email", $rec->email)));
        if ($count_user & $count_mail) {
            $x->$username->error = "Users with same username and e-mail found.";
        } else if ($count_user) {
            $x->$username->error = "Username already exists.";
        } else if ($count_mail) {
            $x->$username->error = "E-mail already registered.";
        }
    }
    return($x);
}


/* Add new users to database.
 *
 * Parameters
 * ==========
 * x : stdClass object
 *      Object as returned by parse_xml_file or parse_single_user or
 *      recieved via _POST["users"].
 *
 * Return
 * ======
 * In case we encounter errors while calling the database, an error
 * will be returned (array with an "error" message). Else, an array
 * with a "success" message is returned.
 */
function add_new_users_to_database($x, $DbHandler, $config) {

    // Prepare query/statement
    $query = "INSERT INTO users (username, displayname, email, password) VALUES (?,?,?,?)";
    $prep_user = $DbHandler->prepare($query);
    if(!$prep_user) { die("Problems preparing the sql query"); }

    $prep_role = $DbHandler->prepare("INSERT INTO users_role (user_id) VALUES (?)");
    if(!$prep_role) { die("Problems preparing the sql query"); }

    // Variable binding. Note that the variables do not yet exist (just binding)
    $prep_user->bind_param("ssss", $username, $displayname, $email, $passmd5);
    $prep_role->bind_param("i", $user_id);

    $DbHandler->query("START TRANSACTION");
    foreach($x as $key=>$rec) {
        // Create the variables for the
        $username = $rec["username"];
        $displayname = $rec["displayname"];
        $email = $rec["email"];
        $password = bin2hex(random_bytes(6));
        $passmd5  = md5($password);

        // Send password to user mail address.
        $subject = "exerciseR - Login details";
        $message = "\r\nDear " . $displayname . "\r\n\r\n"
                  ."A new user with your e-mail address has been registered for the exerciseR"
                  ."available on " . $config->get("system", "url") . ".\r\n\r\n"
                  ."You should now be able to login with the following user details:\r\n"
                  ."* url: " . $config->get("system", "url") . "\r\n"
                  ."* username: " . $username . "\r\n"
                  /////."* password (md5): " . $passmd5 . "\r\n"
                  ."* password: " . $password . "\r\n\r\n";

        // phpmailer: MailHandler instance
        $mail = new MailHandler($config);
        if (!$mail->send($email, $displayname, $subject, $message)) {
            return(array("error"=>"Problems senting mail to \"%s\". Did not create user.",
                         $email));
        }

        // Mail sent? Store into database
        $prep_user->execute();
        // Get new user id
        $res = $DbHandler->query(sprintf("SELECT user_id FROM users WHERE username = \"%s\"", $username));
        $user_id = $res->fetch_object()->user_id;
        $prep_role->execute();

    }
    // Close and commit
    $prep_user->close();
    $prep_role->close();
    $check = $DbHandler->query("COMMIT");

    if (!$check) {
        return(array("error"=>"Problems adding new users to database."));
    } else {
        return(array("success"=>"New users successfully added."));
    }
}

// ------------------------------------------------------------------
// Identify the "mode" in which this script is currently running.
// ------------------------------------------------------------------
if (property_exists($post, "users")) {
    $rval = add_new_users_to_database($post->users, $DbHandler, $config);
# If we got an input argument "xmlfile" via _POST: parse and validate file.
} else if (property_exists($post, "xmlfile")) {
    $rval = parse_xml_file($post->xmlfile);
} else if (check_mode_single_user($post)) {
    // All we have to do is to create a new stdClass object with the
    // same structure as returned by parse_xml_file.
    $rval = parse_single_user($post);
} else {
    $rval = array("error" => "Cannot identify mode of current call to addUsers.php.");
}

// If we got errors: print and stop.
if (is_array($rval) && (isset($rval["error"]) | isset($rval["success"]))) {
    print(json_encode($rval));
    die(0);
}

// Else check if the users already exist (modifies $rval)
$rval = check_if_user_or_mail_exists($rval, $DbHandler);

$DbHandler->close();

print(json_encode($rval));


