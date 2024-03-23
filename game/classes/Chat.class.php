<?php

/**
 * Encapsulates data-access functions for chat messages.
 * Normally chat messages would be sent from the player object so this class isn't used.
 * This could be used to send system messages, perhaps from a management interface or such.
 */
class Chat extends Dbh {

    /**
     * Get a list of messages relevant to the specified parameters
     * @param $mapId - messages sent from this map
     * @param $playerId - only retrieve messages that this player can see
     * @param $startTime - get messages starting from this time defaults to 12h ago
     * @param $afterChatId - only retrieve messages with chatId greater than this
     */
    public static function getMessages(int $mapId, int $playerId, string $startTime, int $afterChatId=null) {

        $dbh = new Dbh();

        $sql = "SELECT cl.id, cl.player_id_src,
            ps.name AS src_name, cl.player_id_tgt, pt.name AS tgt_name,
            message_time, message, cl.map_id, cl.x, cl.y
            FROM chat_log cl
            LEFT JOIN players ps ON cl.player_id_src=ps.id
            LEFT JOIN players pt ON cl.player_id_tgt=pt.id
            WHERE cl.map_id = :id AND message_time >= :startTime
            AND (player_id_tgt = :tgt OR player_id_tgt = 0 OR player_id_tgt IS NULL OR player_id_src = :src OR player_id_tgt = 0 OR player_id_tgt IS NULL) ";

        if (!is_null($afterChatId)) {
            $sql .= " AND cl.id > :afterChatId";
        }

        $sql .= " ORDER BY message_time ASC;";

        $stmt = $dbh->connect()->prepare($sql);
        $stmt->bindParam(':id', $mapId, PDO::PARAM_INT);
        // These are the same variable
        $stmt->bindParam(':tgt', $playerId, PDO::PARAM_INT);
        $stmt->bindParam(':src', $playerId, PDO::PARAM_INT);
        $stmt->bindParam(':startTime', $startTime, PDO::PARAM_STR);
        if (!is_null($afterChatId)) $stmt->bindParam(':afterChatId', $afterChatId, PDO::PARAM_INT);
        $stmt->execute();

        // Return an array of messages;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }



    /**
     * Insert a new message into the chat_log DB table
     * @param $message - contents of the message
     * @param $mapId - map message is sent from
     * @param $x - x position of player sending message
     * @param $y - y position of player sending message
     * @param $playerIdSrc - id that sent the message
     * @param $playerIdTgt - id message was whispered to, if any
     */
    public static function sendMessage(string $message, int $mapId, int $x, int $y, int $playerIdSrc=null, int $playerIdTgt=null): int
    {
        // Insert into the DB, then invoke the constructor
        $dbh = new Dbh();

        $sql = "INSERT INTO chat_log (
            player_id_src,
            player_id_tgt,
            message_time,
            message,
            map_id,
            x,
            y)
        VALUES(
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            ?);
        -- Also ensure the player remains visible if they are actively chatting.
        UPDATE players SET last_action_time = NOW(), visible = 1 WHERE id = ?";
        $conn = $dbh->connect();
        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $playerIdSrc,
            $playerIdTgt,
            $message,
            $mapId,
            $x,
            $y,
            $playerIdSrc]);
        // get the new chat_log id in case it'll be useful
        return (int)$conn->lastInsertId();

    }// end sendMessage static method

}