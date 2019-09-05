<?php


class DbHandler extends mysqli {

    private $config = NULL;

    public function __construct($config) {

        if (!$config instanceof ConfigParser) {
            $e = new Exception("Input to class DbHandler ist not of class ConfigParser.");
            print($e->getMessage()); throw $e;
        }
        $this->config = $config;

        // Depending of the database type to use: set up connection handler.
        $con =   parent::__construct($config->get("mysql", "server"),
                            $config->get("mysql", "user"),
                            $config->get("mysql", "password"),
                            $config->get("mysql", "database"));
        if ($con === false) { throw new Exception("Problems connecting to mysql database."); }

        // Check required tables, create if not yet existing
        $this->_check_table($this->config->get("mysql", "database"), "users");
        $this->_check_table($this->config->get("mysql", "database"), "groups");
        $this->_check_table($this->config->get("mysql", "database"), "users_role");
        $this->_check_table($this->config->get("mysql", "database"), "users_groups");
        $this->_check_table($this->config->get("mysql", "database"), "exercises");
        $this->_check_table($this->config->get("mysql", "database"), "public_exercises");
        $this->_check_table($this->config->get("mysql", "database"), "exercise_mapping");
    }

    /* Check if a specific table exists.
     *
     * Used to check if the database tables exist. If not existing, the table
     * will be crated calling the _create_table function.
     *
     * Parameter
     * =========
     * dbname : str
     *      Name of the database.
     * table : str
     *      Name of the database table.
     *
     * Returns
     * =======
     * No return. If the table does not exist the function will try to create
     * the table. If the table is exists multiple times (should never happen)
     * an error would be thrown.
     */
    private function _check_table($dbname, $table) {
        $sql = sprintf("SELECT count(*) AS count FROM information_schema.tables "
                       ."WHERE table_schema = \"%s\" AND table_name = \"%s\";",
                       $dbname, $table);
        // Get table count (0 = not existing)
        $res  = (int)$this->query($sql)->fetch_object()->count;
        // Check return
        if ($res > 1) {
            throw new Exception(sprintf("Table \"%s\" found multiple times! This is "
               ."a serious issue of the software (sql statement wrong).", $table));
        }
        #if ($this->con instanceof "SQLite3") {
        if ($res == 0) { $this->_create_table($table); }
    }

    /* Creates non-existing database tables
     *
     * Parameter
     * =========
     * table : str
     *      Name of the table.
     */
    private function _create_table($table) {

        // Create users table
        if ($table == "users") {
            $sql = "CREATE TABLE users (\n"
                  ."  user_id  SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                  ."  username VARCHAR(20) NOT NULL,\n"
                  ."  displayname VARCHAR(50) NOT NULL,\n"
                  ."  email    VARCHAR(100) NOT NULL,\n"
                  ."  password VARCHAR(50) NOT NULL, \n"
                  ."  status  ENUM('active', 'inactive') DEFAULT 'active',\n"
                  ."  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  PRIMARY KEY (user_id),\n"
                  ."  UNIQUE (username)\n"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            ## TODO insert demo data
            $this->query("INSERT INTO users (username, displayname, password, email) VALUES "
                        ."('reto', 'Reto Stauffer', md5('reto'), 'reto.stauffer@uibk.ac.at');");
            $this->query("INSERT INTO users (username, displayname, password, email) VALUES "
                        ."('test', 'Some Test User', md5('test'), 'foo@bar.com');");
            $this->query("INSERT INTO users (username, displayname, password, email) VALUES "
                        ."('zeileis', 'Achim  Zeileis', md5('zeileis'), 'Achim.Zeileis@uibk.ac.at');");
            $this->query("INSERT INTO users (username, displayname, password, email, status) VALUES "
                        ."('deadman', 'Deadman', md5('deadman'), 'test@user.com', 'inactive');");

        // Groups table
        } else if ($table == "groups") {
            $sql = "CREATE TABLE groups (\n"
                  ."  group_id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                  ."  groupname VARCHAR(50) NOT NULL,\n"
                  ."  description VARCHAR(300),\n"
                  ."  status  ENUM('active', 'inactive') DEFAULT 'active',\n"
                  ."  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  PRIMARY KEY (group_id)"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            // Demo data
            $this->query("INSERT INTO groups (groupname, description, status) VALUES "
                        ."('S2018', 'R programming class S2019', 'inactive');");
            $this->query("INSERT INTO groups (groupname, description) VALUES "
                        ."('W2019', 'R programming class W2019');");
            $this->query("INSERT INTO groups (groupname, description) VALUES "
                        ."('test', 'test class');");

        // User roles
        } else if ($table == "users_role") {
            $sql = "CREATE TABLE users_role (\n"
                  ."  user_id MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  role    ENUM('participant','mentor','admin') DEFAULT 'participant',\n"
                  ."  created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  UNIQUE (user_id, role)\n"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'reto';");
            $uid = $uid->fetch_object()->user_id;
            $this->query("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");
            $this->query("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'admin');");

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'zeileis';");
            $uid = $uid->fetch_object()->user_id;
            $this->query("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");
            $this->query("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'admin');");

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'test';");
            $uid = $uid->fetch_object()->user_id;
            $this->query("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");

        // User group memberships
        } else if ($table == "users_groups") {
            $sql = "CREATE TABLE users_groups (\n"
                  ."  user_id MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  group_id MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  added  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  UNIQUE (user_id, group_id)\n"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            $this->query("INSERT INTO users_groups (group_id, user_id) VALUES (1, 1);");
            $this->query("INSERT INTO users_groups (group_id, user_id) VALUES (2, 1);");
            $this->query("INSERT INTO users_groups (group_id, user_id) VALUES (2, 2);");
            $this->query("INSERT INTO users_groups (group_id, user_id) VALUES (3, 2);");

        // Create table which takes up the exercises
        } else if ($table == "exercises") {
            $sql = "CREATE TABLE exercises (\n"
                  ."  exercise_id MEDIUMINT NOT NULL AUTO_INCREMENT,\n"
                  ."  name        VARCHAR(50) NOT NULL,\n"
                  ."  description VARCHAR(50) NOT NULL,\n"
                  ."  user_id     MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  created     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  status  ENUM('active', 'inactive') DEFAULT 'active',\n"
                  ."  PRIMARY KEY (exercise_id)\n"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            ## TODO insert demo data
            $this->query("INSERT INTO exercises (name, description, user_id) "
                        ."VALUES ('Create matrix', 'This is just a demo entry', 1);");
            $this->query("INSERT INTO exercises (name, description, user_id) "
                        ."VALUES ('Arithmetic mean with for loop', 'This is just a demo entry', 1);");
            $this->query("INSERT INTO exercises (name, description, user_id) "
                        ."VALUES ('Find index in character vector', 'Just some demo entry', 1);");

        // Create table which takes up the exercises
        } else if ($table == "public_exercises") {
            $sql = "CREATE TABLE public_exercises (\n"
                  ."  exercise_id MEDIUMINT NOT NULL AUTO_INCREMENT,\n"
                  ."  identifier  VARCHAR(30) NOT NULL,\n"
                  ."  created     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  status  ENUM('active', 'inactive') DEFAULT 'active',\n"
                  ."  UNIQUE (exercise_id, identifier)\n"
                  .")";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            ## TODO insert demo data
            $this->query("INSERT INTO public_exercises (exercise_id, identifier) "
                        ."VALUES (1, 'S2020');");
            $this->query("INSERT INTO public_exercises (exercise_id, identifier) "
                        ."VALUES (1, 'W2019');");
            $this->query("INSERT INTO public_exercises (exercise_id, identifier) "
                        ."VALUES (2, 'S2020');");

        // Create mapping table: attribute a specific exercise
        // to a user
        } else if ($table == "exercise_mapping") {
            $sql = "CREATE TABLE exercise_mapping (\n"
                  ."  mapping_id  INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,\n"
                  ."  user_id     MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  exercise_id MEDIUMINT UNSIGNED NOT NULL,\n"
                  ."  hash        VARCHAR(30),\n"
                  ."  created     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  run_counter MEDIUMINT UNSIGNED DEFAULT 0,\n"
                  ."  run_last    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,\n"
                  ."  status      ENUM('assigned', 'retry', 'solved', 'closed') DEFAULT 'assigned',\n"
                  ."  PRIMARY KEY (mapping_id),\n"
                  ."  UNIQUE (user_id, exercise_id)\n" 
                  .");";

            // Create table
            if(!$this->query($sql)) { throw new Exception(sprintf("Table creation failed "
                ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error)); }

            ## TODO insert demo data
            $sql   = "INSERT INTO exercise_mapping (user_id, exercise_id, hash) "
                    ."VALUES (%d, %d, '%s');";
            $users = $this->query("SELECT user_id FROM users;");
            $now   = date("U");
            while($u = $users->fetch_object()) {
                $ex    = $this->query("SELECT exercise_id FROM exercises;");
                while($e = $ex->fetch_object()) {
                    // Generate random hash
                    $hash = md5(sprintf("u %d e %d", $u->user_id, $e->exercise_id));
                    $hash = sprintf("%s-%s-%d", substr($hash, 0, 10), $now, $u->user_id);
                    if (!$this->query(sprintf($sql, $u->user_id, $e->exercise_id, $hash))) {
                        throw new Exception(sprintf("Problems adding demo data"
                            ." (%s; %d): %s", $table, $this->connect_errno, $this->connect_error));
                    }
                }
            }

        } else {
            throw new Exception(sprintf("No rule to create table \"%s\".", $table));
        }
    }

    /* Helper function to throw an error. */
    private function _create_table_failed($name) {
        die(sprintf("Error creating database table \"%s\".", $name));
    }


}

