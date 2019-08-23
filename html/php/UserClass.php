<?php


class UserClass {


    private $DbHandler = NULL;
    private $roles     = NULL;

    /* Handling user details.
     * 
     * Parameter
     * =========
     * user_id : int
     *      User ID
     * DbHandler : DbHandler
     *      Object handling the database connection.
     */
    function __construct($user_id, $DbHandler) {

        $this->user_id   = $user_id;
        $this->DbHandler = $DbHandler;

        // Load roles
        $this->roles = $this->_get_roles($user_id);


    }

    /* Loading user roles
     * 
     * Return
     * ======
     * Returns an array with all user roles. Can be one (typically participant)
     * or multiple (participant, admin, ...).
     */
    private function _get_roles() {

        $results = $this->DbHandler->query("SELECT role FROM users_role WHERE user_id == " .
                                           $this->user_id);
        $roles = array();
        if ($results->numColumns() == 0) { die("Whoops, this user has no role!"); }
        while ($rec = $results->fetchArray()) { array_push($roles, $rec["role"]); }
        return($roles);

    }

    /* Check if user is an administrator.
     *
     * Return
     * ======
     * Boolean true if the user has admin privileges, else false.
     */
    function is_admin() {
        return(in_array("admin", $this->roles) ? true : false);
    }

}
