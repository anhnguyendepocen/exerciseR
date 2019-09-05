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
error_log("WARNING: in getData: REQUEST is used for development purposes, should be _GET.");
$post = (object)$_REQUEST; ##$_POST;
if (empty($post)) { $post->what = "empty"; }

# Default return value (will be re-defined below)
$rval = array("error" => sprintf("Don't know what to do with \"%s\".", $post->what));

# Loading available exercises
switch($post->what) {

    // --------------------------------
    case "users":

        $prep_group = $DbHandler->prepare("SELECT g.group_id, g.groupname FROM users_groups AS ug "
                                         ."LEFT JOIN groups AS g ON ug.group_id = g.group_id "
                                         ."WHERE ug.user_id = ?");

        if (!$prep_group) { die("Problems preparing the query."); }
        $prep_group->bind_param("i", $uid);

        $limit = property_exists($post, "limit") ? sprintf(" LIMIT %d", $post->limit) : "";
        $res = $DbHandler->query("SELECT user_id, username, status, created FROM users "
                                ." ORDER BY created DESC" . $limit);

        $DbHandler->query("START TRANSACTION");
        if ($res) {
            $rval = array();
            while($tmp = $res->fetch_object()) {
                $uid = $tmp->user_id;
                // Find group memberships (name of groups)
                $prep_group->execute();
                $tmp_groups = array();
                if ($prep_group->bind_result($group_id, $groupname)) {
                    while ($prep_group->fetch()) {
                        array_push($tmp_groups, array("id"=>$group_id, "name"=>$groupname));
                    }
                }
                if (count($tmp_groups) > 0) { $tmp->groups = $tmp_groups; }
                array_push($rval, $tmp);
            }
        }
        $prep_group->close();
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
