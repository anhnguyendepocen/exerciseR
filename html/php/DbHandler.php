<?php


class DbHandler extends SQLite3 {

    function __construct($db) {
         $check = $this->open($db);
         // Check required tables, create if not yet existing
         $this->_check_table("users");
         $this->_check_table("users_role");
         $this->_check_table("exercises");
         $this->_check_table("exercise_mapping");
    }

    private function _check_table($table) {

        $sql = sprintf("SELECT count(*) AS count FROM sqlite_master "
                      ."WHERE name = \"%s\" and type=\"table\";",
                      $table);
        $res = (object)$this->query($sql)->fetchArray();
        if ($res->count == 0) { $this->_create_table($table); }

    }

    /* Helper function to set up the different sqlite tables.
     *
     * Parameters
     * ==========
     * table : string
     *      name of the table to be created
     *
     * Returns
     * =======
     * No return, throws an error if table cannot be created.
     */
    private function _create_table($table) {

        // Create users table
        if ($table == "users") {
            $sql = "CREATE TABLE users (\n"
                  ."  user_id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
                  ."  username VARCHAR(50) NOT NULL,\n"
                  ."  password VARCHAR(50) NOT NULL, \n"
                  ."  UNIQUE (username)\n"
                  .")";
            // Create table
            if(!$this->exec($sql)) { $this->_create_table_failed($table); }

            ## TODO insert demo data
            $this->exec("INSERT INTO users (username, password) VALUES ('reto','reto');");
            $this->exec("INSERT INTO users (username, password) VALUES ('test','test');");
            $this->exec("INSERT INTO users (username, password) VALUES ('zeileis','zeileis');");

        // User role, using ENUM (TEXT in sqlite3)
        } else if ($table == "users_role") {
            $sql = "CREATE TABLE users_role (\n"
                  ."  user_id INTEGER NOT NULL,\n"
                  ."  role    TEXT CHECK(role IN ('participant','mentor','admin') ) "
                  ."           NOT NULL DEFAULT 'participant',\n"
                  ."  created Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  UNIQUE (user_id, role)\n"
                  .")";

            // Create table
            if(!$this->exec($sql)) { $this->_create_table_failed($table); }

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'reto';")->fetchArray()["user_id"];
            $this->exec("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");
            $this->exec("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'admin');");

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'zeileis';")->fetchArray()["user_id"];
            $this->exec("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");
            $this->exec("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'admin');");

            $uid = $this->query("SELECT user_id FROM users WHERE username = 'test';")->fetchArray()["user_id"];
            $this->exec("INSERT INTO users_role (user_id, role) VALUES (".$uid.", 'participant');");

        // Create table which takes up the exercises
        } else if ($table == "exercises") {
            $sql = "CREATE TABLE exercises (\n"
                  ."  exercise_id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
                  ."  name VARCHAR(50) NOT NULL,\n"
                  ."  description VARCHAR(50) NOT NULL,\n"
                  ."  user_id INT NOT NULL,\n"
                  ."  created Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP\n"
                  .")";
            print($sql);
            // Create table
            if(!$this->exec($sql)) { $this->_create_table_failed($table); }

            ## TODO insert demo data
            $this->exec("INSERT INTO exercises (name, description, user_id) "
                       ."VALUES ('Create matrix', 'This is just a demo entry', 1);");
            $this->exec("INSERT INTO exercises (name, description, user_id) "
                       ."VALUES ('Arithmetic mean with for loop', 'This is just a demo entry', 1);");
            $this->exec("INSERT INTO exercises (name, description, user_id) "
                       ."VALUES ('Find index in character vector', 'Just some demo entry', 1);");

        // Create mapping table: attribute a specific exercise
        // to a user
        } else if ($table == "exercise_mapping") {
            $sql = "CREATE TABLE exercise_mapping (\n"
                  ."  mapping_id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
                  ."  user_id NOT NULL,\n"
                  ."  exercise_id NOT NULL,\n"
                  ."  hash VARCHAR(20),\n"
                  ."  created Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,\n"
                  ."  run_counter INT DEFAULT 0,\n"
                  ."  run_last DATETIME DEFAULT 0,\n"
                  ."  status INT DEFAULT 0\n"
                  .")";
            // Create table
            if(!$this->exec($sql)) { $this->_create_table_failed($table); }

            ## TODO insert demo data
            $sql   = "INSERT INTO exercise_mapping (user_id, exercise_id, hash) "
                    ."VALUES (%d, %d, '%s');";
            $users = $this->query("SELECT user_id FROM users;");
            $now   = date("U");
            while($u = $users->fetchArray(SQLITE3_NUM)) {
                $ex    = $this->query("SELECT exercise_id FROM exercises;");
                while($e = $ex->fetchArray(SQLITE3_NUM)) {
                    // Generate random hash
                    $hash = md5(sprintf("u %d e %d", $u[0], $e[0]));
                    $hash = sprintf("%s-%s-%d", substr($hash, 0, 10), $now, $u[0]);
                    $this->exec(sprintf($sql, $u[0], $e[0], $hash));
                }
            }

        } else {
            die(sprintf("No rule to create table \"%s\".", $table));
        }
    }

    /* Helper function to throw an error. */
    private function _create_table_failed($name) {
        die(sprintf("Error creating database table \"%s\".", $name));
    }


}

