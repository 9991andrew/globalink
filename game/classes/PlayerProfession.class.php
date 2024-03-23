<?php

/**
 * A Profession that a Player has.
 * Extends Profession, adding professionXP and professionLevel and as a levelUp() method that will
 * check if the level is ready to increase, and if so, increases it.
 */
class PlayerProfession extends Profession {

    protected $playerId;
    protected $professionXP;
    protected $professionLevel;
    protected $nextLevelXP;
    protected $maxLevel;

     /**
     * PlayerProfession constructor, needs array arg with all properties
     * @param array|int $arg - profession_id or  array containing all item values from an SQL query.
     * @param int|null $playerId
     * If necessary we will later support passing a profession ID
     * @throws Exception
     */

    public function __construct($arg, int $playerId=null)
    {
        if (is_int($arg)) {
            $professionId = $arg;
            $sql = "SELECT pp.player_id, p.id, pp.profession_xp, pp.profession_level, p.name, p.require_all_prerequisites,
            (SELECT 1 FROM profession_prerequisites pr2 WHERE pr2.profession_id=p.id LIMIT 1) AS has_prerequisite,
            (SELECT SUM(divide_xp) FROM profession_leveling_view WHERE profession_id=pp.profession_id AND level<=pp.profession_level) as next_level_xp,
            (SELECT MAX(level) FROM profession_leveling_view WHERE profession_id=pp.profession_id) as max_level
            FROM player_professions pp JOIN professions p ON p.id=pp.profession_id
            WHERE pp.profession_id = ? AND player_id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$professionId, $playerId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new InvalidProfessionException($professionId);
            $arg = $stmt->fetch();
        } else if(!is_array($arg)) {
            // arg is probably invalid, we don't support another way to create a PlayerProfession
            throw new InvalidProfessionException();
        }
        // Create the base item given profession_id, profession_name, require_all_prerequisites, has_prerequisite,
        // player_id, profession_xp, profession_level, next_level_xp
        parent::__construct($arg);

        // Add any properties specific to playerItem
        $this->playerId = (int)$arg['player_id'];
        $this->professionXP = (int)$arg['profession_xp'];
        $this->professionLevel = (int)$arg['profession_level'];
        $this->nextLevelXP = (int)$arg['next_level_xp'];
        $this->maxLevel = (int)$arg['max_level'];
    } // end constructor

    public function getPlayerId() {return $this->playerId;}
    public function getProfessionXP() {return $this->professionXP;}
    public function getProfessionLevel() {return $this->professionLevel;}
    public function getNextLevelXP() {return $this->nextLevelXP;}
    public function getMaxLevel() {return $this->maxLevel;}


    /**
    * getClientNpcItemData: Returns essential tile data for client-side interaction
    */
    public function getClientPlayerProfessionData() {
        $ppData = array();
        $ppData['pid'] = (int)$this->id;
        $ppData['pname'] = $this->name;
        $ppData['plvl'] = (int)$this->professionLevel;
        $ppData['pxp'] = (int)$this->professionXP;
        $ppData['nlxp'] = (int)$this->nextLevelXP;
        return $ppData;
    }

    /**
     * Check if the level of this profession should be increased. If so, it increments
     * and gives the player a bonus point and returns true.
     *
     * @return bool
     */
    public function levelUp():bool
    {
        // Making a slight tweak to require GREATER THAN the nextLevelXP, otherwise some professions
        // Will advance for no reason sometimes, even if you haven't done anything relevant.
        // The progression for some professions is not set up very well - it's based on the quests.
        if ($this->professionXP >= $this->nextLevelXP && $this->professionLevel < $this->maxLevel) {
            $this->professionLevel++;
            $sql = "SELECT SUM(divide_xp) AS next_level_xp FROM profession_leveling_view WHERE profession_id = ? AND level <= ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id, $this->professionLevel]);
            $this->nextLevelXP = $stmt->fetch()['next_level_xp'];

            // Do DB updates
            $sql = "UPDATE player_professions SET profession_level = profession_level + 1 WHERE player_id = ? AND profession_id = ?; ";
            $sql.= "UPDATE players SET bonus_point = bonus_point + 1 WHERE id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([
                $this->playerId, $this->id,
                $this->playerId
            ]);

            return true;
        }

        return false;
    }

}