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

# Default return value (will be re-defined below)
$rval = array("error" => sprintf("Don't know what to do with \"%s\".", $post->what));

# Loading available exercises
switch($post->what) {

    // --------------------------------
    case "users":

        $limit = property_exists($post, "limit") ? sprintf(" LIMIT %d", $post->limit) : "";
        $res = $DbHandler->query("SELECT user_id, username, status, created FROM users "
                                ." ORDER BY created DESC" . $limit);
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) { array_push($rval, $tmp); }
        }
        break;


    // --------------------------------
    case "exercises":

        $limit = property_exists($post, "limit") ? sprintf(" LIMIT %d", $post->limit) : "";
        $sql = "SELECT e.*, u.username AS created_by, "
            ."CASE WHEN ea.count IS NULL THEN 0 ELSE ea.count END AS count_assigned, "
            ."CASE WHEN er.count IS NULL THEN 0 ELSE er.count END AS count_retry, "
            ."CASE WHEN es.count IS NULL THEN 0 ELSE es.count END AS count_solved, "
            ."CASE WHEN ec.count IS NULL THEN 0 ELSE ec.count END AS count_closed "
            ."FROM exercises AS e "
            ."LEFT JOIN users AS u on e.user_id = u.user_id "
            ."LEFT JOIN (SELECT exercise_id, count(*) AS count FROM exercise_mapping "
            ."WHERE status = 'assigned' GROUP BY exercise_id) AS ea ON ea.exercise_id = e.exercise_id "
            ."LEFT JOIN (SELECT exercise_id, count(*) AS count FROM exercise_mapping "
            ."WHERE status = 'retry' GROUP BY exercise_id) AS er ON er.exercise_id = e.exercise_id "
            ."LEFT JOIN (SELECT exercise_id, count(*) AS count FROM exercise_mapping "
            ."WHERE status = 'solved' GROUP BY exercise_id) AS es ON es.exercise_id = e.exercise_id "
            ."LEFT JOIN (SELECT exercise_id, count(*) AS count FROM exercise_mapping "
            ."WHERE status = 'closed' GROUP BY exercise_id) AS ec ON ec.exercise_id = e.exercise_id "
            ."ORDER BY u.created DESC " . $limit;
        $res = $DbHandler->query($sql);
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) { array_push($rval, $tmp); }
        }
        break;

    // --------------------------------
    case "groups":

        $limit = property_exists($post, "limit") ? sprintf(" LIMIT %d", $post->limit) : "";
        $res = $DbHandler->query("SELECT * FROM groups ORDER BY groupname " . $limit);
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) { array_push($rval, $tmp); }
        }
        break;

} # End switch


print json_encode($rval);
