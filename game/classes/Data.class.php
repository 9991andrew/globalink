<?php

/**
 * Retrieves miscellaneous information from the MEGAWorld database that are not pertinent to a specific object.
 * Logging functions are included here.
 *
 * This class is a grab-bag container for various utility queries.
 */
class Data extends Dbh {
    /**
     * Data constructor. Doesn't do anything yet
     */
    public function __construct()
    {
    }


    /**
     * getGenders() returns array of genders from DB
     */
    public static function getGenders(): array
    {
        $sql = "SELECT id, name FROM genders WHERE available=1";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getRaces() returns array of races from DB
     */
    public static function getRaces(): array
    {
        $sql = "SELECT id, name, description FROM races";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * getBirthplaces() returns array of birthplaces from DB
     */
    public static function getBirthplaces(): array
    {
        $sql = "SELECT b.id, b.map_id, x, y, b.name, b.description, m.name AS map_name,
        m.map_type_id, mt.name AS map_type_name, mt.description AS map_type_description FROM birthplaces b
        JOIN maps m ON b.map_id=m.id
        JOIN map_types mt ON mt.id=m.map_type_id
        ORDER BY mt.name, b.name, map_id";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getAttributes() returns array of player stat types that are attribute
     */
    public static function getAttributes() {
        $sql = "SELECT * FROM stats WHERE is_attribute = 1 ORDER BY id";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getStats() returns array of all stat types
     */
    public static function getStats() {
        $sql = "SELECT * FROM stats ORDER BY id";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * getProfessions() returns array of all professions
     */
    public static function getProfessions() {
        $sql = "SELECT id, name FROM professions ORDER BY id";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * log() can handle logging various things to the database logs in an efficient way.
     * Generally we want to avoid instantiating classes so that we don't have all the queries involved
     * If we are rapidly logging things (like keystrokes).
     * This is why we aren't instantiating objects like we normally would.
     * @param string $logType -- could be url, key, etc.
     * @param string $note (optional)
     */
    public static function log(string $logType, string $note) {
        if (!isset($_SESSION['playerId'])) throw new Exception('No playerId in session. Log requires the playerId to be set.');

        // We almost always need the information about the current player, so let's grab that from the DB.
        $sql = "SELECT user_id, id, map_id, x, y FROM players WHERE id = ?;";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute([$_SESSION['playerId']]);
        $playerQuery = $stmt->fetch();
        $userId = $playerQuery['user_id'];
        $playerId = $playerQuery['id'];
        $mapId = $playerQuery['map_id'];
        $x = $playerQuery['x'];
        $y = $playerQuery['y'];

        switch ($logType) {
            case 'link': $action=Actions::MOVE_EXTERNAL_LINK; break;
            default: $action = null;
        }

        // Now run the log query
        $sql = "INSERT INTO player_actions_log (user_id, player_id, map_id, x, y, event_time, player_action_type_id, note) VALUES
        (?, ?, ?, ?, ?, NOW(3), ?, ?);";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute([$userId, $playerId, $mapId, $x, $y, $action, $note]);

    }


    /**
     * This takes those massive timestamps and makes them into precise datetime values
     * @param int $t -- timeStamp in ms
     */
    private static function formatMicroTime($t) {
        $t = $t/1000;
        $micro = sprintf("%06d",($t - floor($t)) * 1000000);
        $d = new DateTime( date('Y-m-d H:i:s.'.$micro, floor($t)) );
        return "'".$d->format("Y-m-d H:i:s.u")."'"; // note at point on "u"
    }

    /**
     * Accepts an array of keystroke elements and inserts them into the DB
     * @param int $playerId
     * @param array $keys
     * @throws Exception
     */
    public static function keylog(int $playerId, array $keys)
    {
        // Don't need to do anything if the array is empty
        if (sizeof($keys) == 0) return;
        $dbh = new Dbh();

        $sql = "INSERT INTO key_log (player_id, quest_id, item_id, quest_answer_id, to_player_id, map_id, x, y, keycode, down_time, up_time) VALUES ";
        $c=0;
        foreach ($keys as $key) {
            // Add a comma before the next statement if this isn't the first row to insert
            if ($c++ > 0) $sql.=",";
            // This validation is very important because we're not using parameter binding.
            // We have to ensure that all these values are simply ints/null so they don't pose an injection risk.
            if (!is_int($playerId)) throw new Exception('Only integer values allowed for id.');
            // These are actually timestamps, they should still qualify as INT, though
            if (!is_int($key[1])) throw new Exception('Downtime must be an integer.');
            if (!(is_int($key[2]) || is_null($key[2]))) throw new Exception('Uptime must be an integer.');
            if (!is_int($key[3])) throw new Exception('X must be an integer.');
            if (!is_int($key[4])) throw new Exception('Y must be an integer.');
            if (!is_int($key[5])) throw new Exception('MapId must be an integer.');
            if (!(is_null($key[6]) || is_int($key[6]))) throw new Exception("Quest must be NULL or integer.");
            if (!(is_null($key[7]) || is_int($key[7]))) throw new Exception("itemId must be NULL or integer.");
            if (!(is_null($key[8]) || is_int($key[8]))) throw new Exception("answerId must be NULL or integer.");
            if (!(is_null($key[9]) || is_int($key[9]))) throw new Exception("toPlayerId must be NULL or integer.");
            // If all the fields are null, something is weird, what are we even recording?
            //if ((is_null($key[6]) && is_null($key[7]) && is_null($key[8]) && is_null($key[9]))) throw new Exception("Not enough information about what is being logged.");

            $sql.="($playerId,".
                // Player, quest, item, map, x, y
                (is_null($key[6])?'NULL':$key[6]).",".
                (is_null($key[7])?'NULL':$key[7]).",".
                (is_null($key[8])?'NULL':$key[8]).",".
                (is_null($key[9])?'NULL':$key[9]).",".
                // map_id, x, y
                $key[5].",".$key[3].",".$key[4].",".
                // keycode
                $key[0].",".
                // Downtime
                Data::formatMicroTime($key[1]).",".
                // Uptime
                Data::formatMicroTime($key[2]).")";
        }
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
    }

    /**
     * getLanguages() returns array of supported languages
     */
    public static function getLanguages() {
        $sql = "SELECT name, native_name, locale_id FROM languages WHERE supported = 1 ORDER BY id;";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} // end Data class
