<?php
/**
 * A user account that may log in to MEGA World and have many Players.
 * Handles creation and login of user, returning error if username or password is not found.
 * Uses the user-friendly (but less secure) approach of telling whether the username or password is wrong.
 */
class User extends Dbh {
    protected $id;
    // Name user logs in with
    protected $username;
    protected $password;
    protected $email;
    protected $localeId;
    protected $createdAt;
    protected $lastLoginTime;
    protected $lastPlayerId;

    /**
     * Create a new user given a valid username, passowrd, and optional email and language
     * @param string $username
     * @param string $password
     * @param string|null $email
     * @param string $localeId
     * @return User
     * @throws InvalidPasswordException
     * @throws InvalidUserException
     */
    public static function create(string $username, string $password, string $email=null, string $localeId=Config::DEFAULT_LOCALE) {
        // Insert into the DB, then invoke the constructor
        $dbh = new Dbh();
        $sql = "INSERT INTO users (username, password, email, locale_id, created_at, updated_at, last_login_at)
            VALUES(?, ?, ?, ?, NOW(), NOW(), NOW());";

        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute([$username, sha1($password), $email, $localeId]);
        return new self($username, sha1($password), $email, $localeId);
        // I should now be able to construct a new user and return it
    }


    /**
     * User constructor. Needs a userName and passwordHash
     * @param string $username - username of the user from the users table
     * @param string|null $password - sha1 hash of the user's password
     * @throws InvalidPasswordException
     * @throws InvalidUserException
     */
    public function __construct(string $username, string $password=null)
    {
        $this->username=$username;
        // Check that user exists and throw exception if not.
        $sql = "SELECT * FROM users u WHERE username = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$username]);
        $count = $stmt->rowCount();
        // I suppose here I can use a try/catch to make this more user-friendly
        if ($count == 0) throw new InvalidUserException($username);
        $result = $stmt->fetch();
        $this->id = (int)$result['id'];
        $this->password = $result['password'];
        $this->createdAt = $result['created_at'];
        $this->lastLoginTime = $result['last_login_at'];
        $this->lastPlayerId = is_null($result['last_player_id'])?null:(int)$result['last_player_id'];

        // The following is only used for logins with passwords
        if (!is_null($password))
        {
            // Check that passwordHashes match
            if ($this->password != $password) {
            throw new InvalidPasswordException();
            }

            // If the password was correct, we update the last_login and log to player_actions_log
            $sql = "UPDATE users SET last_login_at=NOW() WHERE id=?;
                INSERT INTO player_actions_log (user_id, player_id, map_id, x, y, event_time, player_action_type_id, note) VALUES
                (?, NULL, NULL, NULL, NULL, NOW(3), ?, NULL);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id, $this->id, Actions::LOGIN]);
        }
    }

    public function getId() {return (int)$this->id;}
    public function getUsername() {return $this->username;}
    public function getEmail() {return $this->email;}
    public function getLocaleId() {return $this->localeId;}

    /**
     * Updates a user's locale_id in the database.
     * @param $localeId string
     */
    public function setLocaleId(string $localeId)
    {
        if ($localeId != $this->localeId) {
            // Only set the locale if it's one of the ones that we support.
            foreach (Data::getLanguages() as $language) {
                if ($language['locale_id'] == $localeId) {
                    $sql = "UPDATE users SET locale_id = ? WHERE id = ?;";
                    $stmt = $this->connect()->prepare($sql);
                    $stmt->execute([$localeId, $this->id]);

                    $this->localeId = $localeId;
                }
            }
        }
    }

    /**
     * getPlayers() returns an array of all this character's player IDs.
     * Optionally I could also return the player objects, but perhaps that's excessive.
     */
    public function getPlayerIds() {
        $sql = "SELECT id FROM players WHERE user_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);
        // Return the data as an array of ints
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getLastPlayerId() {
        return $this->lastPlayerId;
    }

    /**
     * Sets the last id when a user selects a player.
     * @param int $lastPlayerId - if null, this clears the value (sets it to NULL)
     */
    public function setLastPlayerId(int $lastPlayerId=null) {
        $this->lastPlayerId = $lastPlayerId;
        $sql = "UPDATE users SET last_player_id = ? WHERE id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->lastPlayerId, $this->id]);
    }

    public function getLastLoginTime() {
        return $this->lastLoginTime;
    }

    /**
     * All this really does is log the logout and destroy the session.
     */
    public function logout() {
        $sql = "UPDATE players SET visible=0 WHERE user_id = ?;
            INSERT INTO player_actions_log (user_id, player_id, map_id, x, y, event_time, player_action_type_id, note) VALUES
            (?, NULL, NULL, NULL, NULL, NOW(3), ?, NULL);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id, $this->id, Actions::LOGOUT]);
        try {
            session_destroy();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    /**
     * Allows a user to change their password.
     * This is a pretty basic function so it must be used carefully with a lot of validation up-front.
     */
    public function changePassword($newPassword)
    {
        $this->password = sha1($newPassword);
        $sql = "UPDATE users SET password = ? WHERE id= ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->password, $this->id]);
    }

    /**
     * Delete user by username.
     * @param $username string name of user
     */
    static public function delete($username) {

        try {
            $user = new User($username);
        } catch (InvalidUserException $e) {
            // If the user doesn't exist, there's nothing to do.
            return;
        }
        $userId = $user->getId();
        // First need to loop through all the user's players and destroy them.

        $playerIds = $user->getPlayerIds();
        foreach($playerIds as $playerId) {
            Player::delete((int)$playerId);
        }

        $sql = "DELETE FROM users WHERE id = :id;";
        // Might want to log the delete of the user
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

}// end user class
