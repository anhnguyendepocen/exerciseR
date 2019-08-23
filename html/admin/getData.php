<?php

require_once("../php/ConfigParser.php");
$config = new ConfigParser("../../files/config.ini", "..");

require_once("../php/DbHandler.php");
$db = new DbHandler($config->get("sqlite3", "dbfile"));

require_once("../php/LoginHandler.php");
$LoginHandler = new LoginHandler($db);

# Convert _POST to object.
# TODO: WARNING: needs to be POST!
$post = (object)$_POST;
if (empty($_POST)) { $post->what = "empty"; }

$post = (object)$_REQUEST;
if (empty($_REQUEST)) { $post->what = "empty"; }

# Default return value (will be re-defined below)
$rval = array("error" => sprintf("Don't know what to do with \"%s\".", $post->what));

# Loading available exercises
switch($post->what) {

    # ---------------------------------------
    case "exercises":

        if (!property_exists($post, "limit")) { $post->limit = 10; }
        $result = $db->query("SELECT u.username, e.* from exercises AS e "
                            ."LEFT OUTER JOIN users aS u "
                            ."ON u.user_id = e.user_id "
                            ."ORDER BY created DESC "
                            ."LIMIT " . sprintf("%d", $post->limit));

        $rval      = new stdClass();
        $midnight  = floor((int)date("U") / 86400) * 86400;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row["exercise_id"];
            # Age
            $ts = strtotime($row["created"]); # Time stamp (unix time stamp)
            $fmt = $config->get("datetime format", $ts < $midnight ? "date" : "time");
            $created = date($fmt, $ts);
            # Append
            $rval->$id = array("ID"      => $row["exercise_id"],
                               "created" => $created,
                               "name"    => $row["name"],
                               "creator" => $row["username"]);
        }

        break;

} # End switch


print json_encode($rval);
