<?php


class LoginHandler {

    private $db = NULL;

    function __construct($db, $post = NULL) {

        // Store database object
        $this->db = $db;

        // Start session
        session_start();

        // Store post args
        if (is_null($post)) { $post = $_POST; }
        $post = is_null($post) ? (object)$_POST : (object)$post;

        // Logut
        if (property_exists($post, "logout")) {
            $this->logout();
            $this->show_login_form();
        }

        // If $request contains username and password:
        // check if login is valid
        if (property_exists($post, "username") & property_exists($post, "password")) {
            if (!strlen($post->username) == 0 & !strlen($post->password) == 0) {
                $user_id = $this->check_login($post);
            }
            // Invalid login
            if (is_bool($user_id)) {
                print("Invalid user name or passsword. Try again.\n");
            } else {
                $_SESSION["user_id"] = $user_id;
                $_SESSION["username"] = $post->username;
            }
        }

        if(!isset($_SESSION["user_id"])) { $this->show_login_form(); }

    }

    /* Display login form
     *
     * No input, no output, simply shows the login form and
     * stops script execution.
     */
    private function show_login_form() {
        ?>
        <form method="POST">
        <input type="text" name="username" /><br />
        <input type="password" name="password" /><br />
        <input type="submit" value="Login" name="submit" /><br />
        </form>
        <?php
        // Show login form and stop execution.
        die(0);
    }

    /* Check login credentials.
     *
     * Parameter
     * =========
     * post : object
     *     object containing the login credentials. 'username' and 'password'
     *     have to be defined.
     *
     * Returns
     * =======
     * Returns boolean true if login is allowed, else false.
     */
    public function check_login($post) {

        $sql = "SELECT user_id FROM users WHERE username = \"%s\" AND password = \"%s\";";
        $res = (object)$this->db->query(sprintf($sql, $post->username, $post->password))->fetchArray();
        return(property_exists($res, "user_id") ? $res->user_id : false);

    }

    /* Display logout form.
     */
    public function logout_form() {
        ?>
        <form method="POST">
        <input type="hidden" value="logout" name="logout" />
        <input class="btn btn-info" type="submit" value="Logout (<?php print($_SESSION["username"]); ?>)" name="submit" /><br />
        </form>
        <?php
    }

    /* Destroy current session */
    private function logout() {
        session_destroy();
    }


}
