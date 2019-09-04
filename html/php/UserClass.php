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

        if (!$DbHandler instanceof DbHandler) {
            throw new Exception("Wrong input (not an object of class DbHandler)");
        }

        $this->user_id   = $user_id;
        $this->DbHandler = $DbHandler;

        // Load roles
        $this->roles = $this->_get_roles($user_id);


    }

    /* Return user_id
     *
     * Return
     * =======
     * Returns the user_id, integer.
     */
    public function user_id() { return((int)$this->user_id); }

    /* return username
     * 
     * Return
     * ======
     * Returns the user name (string).
     */
    public function username() {
        $res = $this->DbHandler->query(sprintf("SELECT username FROM users WHERE user_id = %d", 
                                               $this->user_id));
        return($res->fetch_object()->username);
    }


    /* Loading user roles
     * 
     * Return
     * ======
     * Returns an array with all user roles. Can be one (typically participant)
     * or multiple (participant, admin, ...).
     */
    private function _get_roles() {

        $res = $this->DbHandler->query(sprintf("SELECT role FROM users_role WHERE user_id = %d;",
                                       $this->user_id));
        $roles = array();
        if ($res->num_rows == 0) { die("Whoops, this user has no role!"); }
        while ($rec = $res->fetch_object()) { array_push($roles, $rec->role); }
        return($roles);

    }

    /* Check if user is an administrator.
     *
     * Return
     * ======
     * Boolean true if the user has admin privileges, else false.
     */
    public function is_admin() {
        return(in_array("admin", $this->roles) ? true : false);
    }

}
