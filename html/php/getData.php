<?php
# Loading required config
function __autoload($name) {
    $file = sprintf("../php/%s.php", $name);
    try {
        require($file);
    } catch (Exception $e) {
        throw new MissingException("Unable to load \"" . $file . "\".");
    }
}

$config = new ConfigParser("../../files/config.ini", "..");
$DbHandler = new DbHandler($config);
$LoginHandler = new LoginHandler($DbHandler);

# Convert _POST to object.
# TODO: WARNING: needs to be POST!
$post = (object)$_POST;
if (empty($_POST)) { $post->what = "empty"; }

$post = (object)$_REQUEST;
if (empty($_REQUEST)) { $post->what = "empty"; }
if (!property_exists($post, "limit")) { $post->limit = 10; }

# Default return value (will be re-defined below)
$rval = array("error" => sprintf("Don't know what to do with \"%s\".", $post->what));

# Loading available exercises
switch($post->what) {

    # ---------------------------------------
    case "exercises":

        $result = $DbHandler->query("SELECT u.username, e.* from exercises AS e "
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

    case "groups":

        $res = $DbHandler->query("SELECT * FROM groups LIMIT " . sprintf("%d", $post->limit));
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) { array_push($rval, $tmp); }
        }
        break;

    case "users":

        $res = $DbHandler->query("SELECT user_id, username FROM users "
                                ." ORDER BY username ASC LIMIT " . sprintf("%d", $post->limit));
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) { array_push($rval, $tmp); }
        }
        break;


} # End switch


print json_encode($rval);
