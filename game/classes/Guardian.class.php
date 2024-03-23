<?php

/**
 * Guardian class.
 * This class is used to manage the guardian's data.
 * This class handles the communication with the guairdian as well as guardian's avatar
 */
class guardian extends Dbh {

    private $courses;
    private $selectedCourse;

    public function checkGaurdianOwnership($guardianId) {
        $dbh = new Dbh();
        $sql = "SELECT player_id FROM guardian_player_relations WHERE guardian_id = $guardianId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        // Return the data as an array of ints
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)); 
    }

    public function getGuardianColor($guardianId) {
        $dbh = new Dbh();
        $sql = "SELECT color FROM guardians WHERE id = $guardianId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['color'];
    }

    public function getGuardianIds() {
        $dbh = new Dbh();
        $sql = "SELECT guardian_id FROM guardian_player_relations";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        // Return the data as an array of ints
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getAllGuardianIds() {
        $dbh = new Dbh();
        $sql = "SELECT id FROM guardians";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        // Return the data as an array of ints
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function insertNewGuardian($guardianId,$genderId,$raceId,$color) {
        $dbh = new Dbh();
        $sql = "INSERT INTO guardians (id,race_id,gender_id,color) VALUES ($guardianId, $raceId, $genderId, '$color')";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
    }

    public function updateCurrGuardian($guardianId,$genderId,$raceId) {
        $dbh = new Dbh();
        $sql = "UPDATE guardians SET race_id = $raceId, gender_id = $genderId WHERE id = $guardianId;";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
    }

    public function linkPlayerToGuardian($playerId, $guardianId) {
        $dbh = new Dbh();
        $sql = "UPDATE guardian_player_relations SET player_id = $playerId, guardian_id = $guardianId WHERE player_id = $playerId;";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
    }

    public function fetchMaxGuardianId() {
        $dbh = new Dbh();
        $sql = "SELECT id from guardians ORDER BY id DESC LIMIT 1";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['id'];
    }

    public function getGenderId(int $playerId) {
        $guardianId = $this->getGuardianIdByPlayerId($playerId);
        $dbh = new Dbh();
        $sql = "SELECT gender_id FROM guardians WHERE id = $guardianId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return intval($result['gender_id']);
    }

    public function getColor(int $playerId) {
        $guardianId = $this->getGuardianIdByPlayerId($playerId);
        $dbh = new Dbh();
        $sql = "SELECT color FROM guardians WHERE id = $guardianId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return ($result['color']);
    }

    public function getRaceId(int $playerId) {
        $guardianId = $this->getGuardianIdByPlayerId($playerId);
        $dbh = new Dbh();
        $sql = "SELECT race_id FROM guardians WHERE id = $guardianId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return intval($result['race_id']);
    }

    public function linkPlayerWithGuardian(int $playerId, int $guardianId) {
        $dbh = new Dbh();
        $sql = "INSERT INTO guardian_player_relations (player_id, guardian_id) VALUES ($playerId, $guardianId)";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
    }

    public function getGuardianIdByPlayerId(int $playerId) {
        $dbh = new Dbh();
        $sql = "SELECT guardian_id FROM guardian_player_relations WHERE player_id = $playerId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['guardian_id'];
    }

    public function checkIfPlayerHasGuardian(int $playerId) {
        $dbh = new Dbh();
        $sql = "SELECT guardian_id FROM guardian_player_relations WHERE player_id = $playerId";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        if($result['guardian_id'] == null) {
            return false;
        } else {
            return true;
        }
    }

    public function getAvailableCourses(int $playerId) {

        $courses = ['eng6420']; // Default.

        $conn = $this->connect();
        $query = "SELECT map_id FROM players WHERE id=?;";
        $stmt = $conn->prepare($query);
        $stmt->execute([$playerId]);
        $mapId = $stmt->fetch()['map_id'];

        $query = "SELECT * FROM ask4 WHERE map_id=?;";
        $stmt = $conn->prepare($query);
        $stmt->execute([$mapId]);

        $rows = $stmt->fetchAll();

        if (count($rows) > 0) { // This map has custom courses selected.

            $courses = [];
            foreach ($rows as $row) {
                $courses[] = $row['course_name'];
            }
        }

        return $courses;
    }

    public function getAvailableCoursesHTML(int $mapId) {

        $courses = ['eng6420']; // Default.

        $query = "SELECT * FROM ask4 WHERE map_id=?;";
        $stmt = $this->connect()->prepare($query);
        $stmt->execute([$mapId]);

        $rows = $stmt->fetchAll();

        if (count($rows) > 0) { // This map has custom courses selected.

            $courses = [];
            foreach ($rows as $row) {
                $courses[] = $row['course_name'];
            }
        }

        $html = "<ul><li><b>Available Courses</b></li>";
        foreach ($courses as $c) {
            $html .= '<li>' . $c . '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Get a list of chat messages relevant to the specified player
     * @param $playerId - player id
     * @return array
     */
    public function getMessagesGuardian (int $playerId) {
        $sql="SELECT * FROM guardian_log 
        WHERE player_id = ${playerId};";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute();
        // Return an array of messages;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }// end getMessages()

    public function messageIsCommand(string $message, int $playerId, int $guardianId) {

        if (stripos($message, '/help') !== false) {

            $sql = "INSERT INTO guardian_log (player_id, guardian_id, message_time, ".
                 "direction, confirmation, query_status, message) ".
                 "VALUES (?, ?, NOW(), ?, ?, ?, ?);";
            $conn = $this->connect();
            $stmt = $conn->prepare($sql);
            $stmt->execute([$playerId, $guardianId, 0, '', 1, $message]);

            $msg = "Sending '/help' will show this help message. ".
                 "Sending '/use {course name}' will set or change the course the guardian answers from.";

            $stmt = $conn->prepare($sql);
            $stmt->execute([$playerId, $guardianId, 1, '', 1, $msg]);

            return true;

        } else if (stripos($message, '/use') !== false) {

            $i = stripos($message, '/use') + 4;
            $tail = substr($message, $i);
            $words = explode(' ', $tail);

            $courses = $this->getAvailableCourses($playerId);
            $selectedCourse = $courses[0];
            $conn = $this->connect();

            foreach ($words as $w) {
                if (in_array($w, $courses)) {
                    $selectedCourse = $w;

                    $sql = "DELETE FROM player_course WHERE player_id=?;";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$playerId]);

                    $sql = "INSERT INTO player_course (player_id, course_name) ".
                         "VALUES (?, ?);";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$playerId, $w]);
                    break;
                }
            }

            $sql = "INSERT INTO guardian_log (player_id, guardian_id, message_time, ".
                 "direction, confirmation, query_status, message) ".
                 "VALUES (?, ?, NOW(), ?, ?, ?, ?);";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$playerId, $guardianId, 0, '', 1, $message]);

            $stmt = $conn->prepare($sql);
            $stmt->execute([$playerId, $guardianId, 1, '', 1, 'Using course '.$selectedCourse]);

            return true;
        }

        return false;
    }

    /**
     * Insert a new message into the guardian_log DB table
     * @param $message - contents of the message
     * @param $playerId - player id
     */
    public function sendMessageGuardian(string $message, int $playerId, int $guardianId): int
    {
        if ($this->messageIsCommand($message, $playerId, $guardianId)) {
            return 0;
        }

        // Insert into the DB, then invoke the constructor
        $dbh = new Dbh();
        $conn = $dbh->connect();

        // Get a default or custom selected course.
        $courses = $this->getAvailableCourses($playerId);
        $selectedCourse = $courses[0];

        $sql = "SELECT * FROM player_course WHERE player_id=?;";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$playerId]);
        $rows = $stmt->fetchAll();

        if (count($rows) > 0) {
            $selectedCourse = $rows[0]['course_name'];
        }

        // if the player has a valid registration
        $url = 'https://guardian.megaworld.game-server.ca/services/send_question.php';
        $data = array('playerId' => $playerId, 'question' => $message, 'courseName' => $selectedCourse);
        
        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */ }
        
        $confirmation = $result;

        $sql = "INSERT INTO guardian_log_answer (
            player_id,
            query_time,
            confirmation)
        VALUES(
            ?,
            NOW(),
            ?);";
        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $playerId,
            $confirmation]);

        $query_status = 0;
        $direction = 0;

        $sql = "INSERT INTO guardian_log (
            player_id,
            guardian_id,
            message_time,
            direction,
            confirmation,
            query_status,
            message)
        VALUES(
            ?,
            ?,
            NOW(),
            ?,
            ?,
            ?,
            ?);";
        $conn = $dbh->connect();
        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $playerId,
            $guardianId,
            $direction,
            $confirmation,
            $query_status,
            $message]);

        
        // get the new chat_log id in case it'll be useful
        return (int)$conn->lastInsertId();
    }

    // check is summary is available
    public function checkForSummary(int $playerId) {
        $dbh = new Dbh();
        $sql = "SELECT confirmation FROM guardian_log WHERE player_id = $playerId AND query_status = 0 LIMIT 1";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        // Return the data as an array of ints
        $confirmation = $stmt->fetch();

        if ($confirmation) {
            // if the player has a valid registration
            $url = 'https://guardian.megaworld.game-server.ca/services/status_check.php';
            $data = array('playerId' => $playerId, 'confirmation' => $confirmation[0]);

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if ($result === FALSE) { /* Handle error */ }

            $summary = $result;

            $direction = 1;
            $query_status = 1;

            $guardianId = $this->getGuardianIdByPlayerId($playerId);

            if($summary == 'in queue') {
                $sql = "UPDATE guardian_log_answer SET query_time = now() WHERE player_id = $playerId AND confirmation = '$confirmation[0]';";
                $stmt = $dbh->connect()->prepare($sql);
                $stmt->execute();
            }
            else {
                $sql = "INSERT INTO guardian_log (
                    player_id,
                    guardian_id,
                    message_time,
                    direction,
                    confirmation,
                    query_status,
                    message)
                VALUES(
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?,
                    ?,
                    ?);";
                $conn = $dbh->connect();
                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $playerId,
                    $guardianId,
                    $direction,
                    $confirmation[0],
                    $query_status,
                    $summary]);
                
                $sql = "UPDATE guardian_log SET query_status = 1 WHERE player_id = $playerId AND confirmation = '$confirmation[0]';";
                $stmt = $dbh->connect()->prepare($sql);
                $stmt->execute();

                return true;
            }
        }

        return false;
    }

    public function fetchGuardianAvatar(int $playerId) {
        $guardianId = $this->getGuardianIdByPlayerId($playerId);
        $dbh = new Dbh();
        $sql = "SELECT ai.id, ai.name, ai.filename, ai.svg_code, color1, color2, color3, color4, ait.layer_index, ai.disabled,
        ai.avatar_image_type_id, ai.gender_id, ai.race_id, ai.color_qty, ait.name as avatar_image_type_name, guardian_id
        FROM guardian_avatar_images pai
        JOIN avatar_images ai ON pai.avatar_image_id=ai.id
        JOIN avatar_image_types ait ON ai.avatar_image_type_id=ait.id
        WHERE guardian_id = $guardianId ORDER BY ait.layer_index;";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $guardianAvatarImages = array();
        // We can create items with no DB query by passing the whole row to the PlayerItem constructor
        while ($row = $stmt->fetch())
        {
            array_push($guardianAvatarImages, new GuardianAvatarImage($row));
        }
        return  $guardianAvatarImages;
    }

    /**
     * showAvatar() returns a div with all the avatar parts displayed within
     * @return string
     */
    public function showGuardianAvatar(int $playerId) {
        $guardianStatus = $this->checkIfPlayerHasGuardian($playerId);
        $html = "";
        $guardianAvatarData = [];
        if($guardianStatus) {
            $guardianAvatarData = $this->fetchGuardianAvatar($playerId);
        } else {
            // Link player to the default guardian
            $this->linkPlayerWithGuardian($playerId, 1);
            $guardianAvatarData = $this->fetchGuardianAvatar(1);
        }
        // CSS padding hack to preserve aspect ratio. Real aspect ratio support is coming to CSS soon!
        $html .= '<div class="avatar relative w-full" style="padding-bottom:139%;">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 330 460" id="pa'.$playerId.'" class="absolute inset w-full h-auto">';        
        foreach ($guardianAvatarData as $image) {
            $html .= Helpers::svgClean((string)$image->getSvgCode(), $image->getAvatarImageTypeId(), $image->getColor1(), $image->getColor2(), $image->getColor3(), $image->getColor4());
        }
        $html .= '</svg>';
        $html .= '</div>';
        return $html;
    }

    public function fetchGuardianAvatarById(int $guardianId) {
        $dbh = new Dbh();
        $sql = "SELECT ai.id, ai.name, ai.filename, ai.svg_code, color1, color2, color3, color4, ait.layer_index, ai.disabled,
        ai.avatar_image_type_id, ai.gender_id, ai.race_id, ai.color_qty, ait.name as avatar_image_type_name, guardian_id
        FROM guardian_avatar_images pai
        JOIN avatar_images ai ON pai.avatar_image_id=ai.id
        JOIN avatar_image_types ait ON ai.avatar_image_type_id=ait.id
        WHERE guardian_id = $guardianId ORDER BY ait.layer_index;";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $guardianAvatarImages = array();
        // We can create items with no DB query by passing the whole row to the PlayerItem constructor
        while ($row = $stmt->fetch())
        {
            array_push($guardianAvatarImages, new GuardianAvatarImage($row));
        }
        return  $guardianAvatarImages;
    }

    public function showGuardianAvatarById(int $guardianId) {
        $html = "";
        $guardianAvatarData = [];
        $guardianAvatarData = $this->fetchGuardianAvatarById($guardianId);
        // CSS padding hack to preserve aspect ratio. Real aspect ratio support is coming to CSS soon!
        $html .= '<div class="avatar relative w-full" style="padding-bottom:139%;">';
        $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 330 460" class="absolute inset w-full h-auto">';        
        foreach ($guardianAvatarData as $image) {
            $html .= Helpers::svgClean((string)$image->getSvgCode(), $image->getAvatarImageTypeId(), $image->getColor1(), $image->getColor2(), $image->getColor3(), $image->getColor4());
        }
        $html .= '</svg>';
        $html .= '</div>';
        return $html;
    }

    public function addAvatarImage($arg, $color1=null, $color2=null, $color3=null, $color4=null, int $guardianId)
    {
        if (is_int($arg)) $avatarImage = new AvatarImage($arg);
        else $avatarImage = $arg;
        $avatarImageId = $avatarImage->getId();
        $player = new Player((int)$_POST['playerId']);

        $sql = "DELETE pai FROM guardian_avatar_images pai JOIN avatar_images ai ON ai.id=pai.avatar_image_id
                WHERE pai.guardian_id = ? AND ai.avatar_image_type_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->getGuardianIdByPlayerId($player->getId()), $avatarImage->getAvatarImageTypeId()]);

        $sql = "INSERT INTO guardian_avatar_images (
            guardian_id,
            avatar_image_id,
            color1,
            color2,
            color3,  
            color4,
            created_at,
            updated_at
        )   
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            now(),
            now()
        );";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([
            $guardianId,
            $avatarImageId,
            $color1,
            $color2,
            $color3,
            $color4
        ]);
    }

    public function generateRandomAvatar($raceId, $genderId, $guardianId) {
        $dbh = new Dbh();
        // Get list of random avatar items, one for each category.
        // Pick primary and secondary colors for all items, leave tertiary to null (which should be rendered as transparent)
        $sql = "SELECT ait.id,
            (SELECT id FROM avatar_images WHERE avatar_image_type_id=ait.id
                    AND (race_id = ? OR race_id IS NULL) AND (gender_id = ? OR gender_id IS NULL)
                    ORDER BY RAND() LIMIT 1) AS avatar_image_id,
            (SELECT css_color FROM avatar_image_type_colors WHERE avatar_image_type_id=ait.id ORDER BY RAND() LIMIT 1) AS color1,
            (SELECT css_color FROM avatar_image_type_colors WHERE avatar_image_type_id=ait.id ORDER BY RAND() LIMIT 1) AS color2
        FROM avatar_image_types ait
        -- Don't pick items when there are no options
        WHERE (SELECT COUNT(1) FROM avatar_images WHERE avatar_image_type_id=ait.id
            AND (race_id = ? OR race_id IS NULL) AND (gender_id = ? OR gender_id IS NULL)) > 0;";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute([
            $raceId,
            $genderId,
            $raceId,
            $genderId
        ]);
        $avatarParts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Now loop through categories and randomly select an item from each, give that to the player
        foreach($avatarParts as $avatarPart) {
            if (!is_null($avatarPart['id']))
                $this->addAvatarImage((int)$avatarPart['avatar_image_id'], $avatarPart['color1'], $avatarPart['color2'], null, null, $guardianId);
        }
    }

    public function create(int $playerId, int $guardianId, int $raceId, int $genderId, string $color) {
        // Inserting a new Guardian Id into Guardians table
        $this->insertNewGuardian($guardianId, $genderId, $raceId, $color);
        // Linking the player with the new created Guardian
        $this->linkPlayerToGuardian($playerId,$guardianId);
        // Randomly assign the new player avatar items
        $this->generateRandomAvatar($raceId,$genderId,$guardianId);
    }
}
