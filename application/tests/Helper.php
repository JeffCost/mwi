<?php namespace Tests;
// application/libraries/tests/helper.php

use \Laravel\CLI\Command as Command;
/*
 The test helper
*/
class Helper {

    /*
     Run the migrations in the test database
    */
    public static function migrate()
    {

        // If there is not a declaration that migrations have been run'd
        if( ! isset($GLOBALS['migrated_test_database']))
        {
            // Run migrations
            require path('sys').'cli/dependencies'.EXT;

            $which_db = \Config::get('database.default');
            $database = \Config::get('database.connections.'.$which_db);

            $migration_table = null;
            if($which_db == 'mysql')
            {
                $query = "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = ? AND table_name = ?";
                $migration_table = \DB::query($query, array($database['database'], $database['prefix'].'laravel_migrations'));
            }

            // if($which_db == 'sqlite')
            // {
            //     //$migration = "SELECT * FROM {$database['database']}.sqlite_master WHERE type='table'";
            //     //sqlite3_exec(budb, "SELECT sql FROM sqlite_master WHERE sql NOT NULL"
            //     \sqlite_open(":memory:", $database['database']);
            //     $migration = \sqlite_exec($database['database'], "SELECT sql FROM sqlite_master WHERE sql NOT NULL");
            // }

            if(isset($migration_table['0']->count) and $migration_table['0']->count  == '0')
            {
                Command::run(array('migrate:install'));
                echo "\n";
                Command::run(array('migrate'));
            }
            else
            {
                Command::run(array('migrate:reset'));
                echo "\n";
                Command::run(array('migrate'));
            }


            //Insert basic data

            // Declare that migrations have been run'd
            $GLOBALS['migrated_test_database'] = true;
        }
    }

    /*
     Enable sessions to be used in tests. For
     authentication purposes.
    */
    public static function use_sessions()
    {
        \Session::started() or \Session::load();
    }

    /*
     Simulates a request to the router re-setting
     the Method
    */
    public static function http_request($method, $route, $post_data = array())
    {
        $request = \Router::route($method, $route);
        
        $post_data[\Session::csrf_token] = \Session::token();

        \Request::setMethod($method);
        
        \Request::foundation()->request->add($post_data);

        return $request->call();
    }
}