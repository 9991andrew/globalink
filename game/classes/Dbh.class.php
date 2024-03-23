<?php

/**
 * The root class for data-access classes in MEGA World v3. Provides a connect() method that
 * allows access to the 'mega' database. This is configured to use the DB in UTC, so when retrieving
 * times, they should be passed to a JS date object so they can be shown to the user in their local timezone.
 */
class Dbh {
    /**
     * Creates a connection to the MEGA database. If necessary, modify the values in the Config class
     * to set the correct database user, password, port, and host.
     *
     * @return PDO
     */
    protected function connect() {
        // Try to read the database configuration .ini file and show an error if it can not be found.
        try {
            $config = parse_ini_file(Config::DATABASE_CONFIG_FILE??'../.megadb.ini');
            if (!$config) {
                echo '<pre><code style="color:red;">.megadb.ini file could not be found at <b>'.Config::DATABASE_CONFIG_FILE.'</b>.';
                echo '<br><br>Ensure the correct path is set for DATABASE_CONFIG_FILE in classes/Config</code></pre>';
                exit('Error reading database configuration');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            exit('Error reading database configuration.');
        }
        $dsn = "mysql:host=".$config['DB_HOST'].";port=".$config['DB_PORT'].";dbname=".$config['DB_NAME'];

        // time offset value we can use for MySQL to keep PHP and MySQL time in sync
        date_default_timezone_set($config['TIME_ZONE']??'UTC');
		try {
		    // The ATTR_INIT_COMMAND ensure the time zone is set to TIME_ZONE
            $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASSWORD'],
                [PDO::MYSQL_ATTR_INIT_COMMAND =>"SET time_zone = '".date('P')."'"]);
            $pdo->setAttribute(PDO::FETCH_ASSOC, PDO::ATTR_DEFAULT_FETCH_MODE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, PDO::ERRMODE_EXCEPTION);
		    return $pdo;
        } catch (PDOException $e) {
		    echo 'Connection failed: '.$e->getMessage();
		    exit;
        }

	}


}

