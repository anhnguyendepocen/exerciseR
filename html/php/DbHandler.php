<?php


class DbHandler extends SQLite3 {

    function __construct($dbname = "test.db", $dbdir = ".") {
         $check = $this->open(sprintf("%s/%s", $dbdir, $dbname));
         if ($check) {
             die("Cannot open database connection!");
         }

         // Check required tables, create if not yet existing
         $this->_check_table("users");
    }

    private function _check_table($table) {

        $sql = sprintf("SELECT count(*) AS count FROM sqlite_master "
                      ."WHERE name = \"%s\" and type=\"table\";",
                      $table);
        $res = (object)$this->query($sql)->fetchArray();
        if ($res->count == 0) { $this->_create_table($table); }

    }

    private function _create_table($table) {
        if ($table == "users") {
            $sql = "CREATE TABLE users (\n"
                  ."  user_id INTEGER PRIMARY KEY AUTOINCREMENT,\n"
                  ."  username VARCHAR(50) NOT NULL,\n"
                  ."  password VARCHAR(50) NOT NULL, \n"
                  ."  UNIQUE (username)\n"
                  .")";
            print($sql);
            $check = $this->exec($sql);
            if(!$check) {
                die(sprintf("Error creating database table \"%s\".", $table));
            }
            ## TODO insert demo data
            $this->exec("INSERT INTO users (username, password) VALUES ('reto','reto');");
            $this->exec("INSERT INTO users (username, password) VALUES ('test','test');");
        }
    }

}

