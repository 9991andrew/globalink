<?php

/**
* Non Player Character information. Has methods to specify what items, professions, and quests are available for a given player.
*/
class Npc extends Dbh {
    protected $id;
    protected $name;
    protected $mapId;
    protected $level;
    protected $yMax;
    protected $yMin;
    protected $xMax;
    protected $xMin;
    protected $iconId;
    protected $imageFilename;
    protected $tnFilename;
    protected $tileProbability;

    // Some NPCs are portal keepers and allow the player to move around (for a price)
    protected $npcPortals;

    // These three variables are just booleans that tell whether a check should be done to see
    // if the NPC has anything to offer a player.

    // Some NPCs have items a player can buy or get if the player is doing a quest.
    // This will be contingent on the player's state, what quests they are doing, etc

    protected $hasItems;

    // NPCs often have quests they can give the player if the player meets the prerequisites
    protected $givesQuests;

    // NPCs may be the target of a quest so the player can complete/'report' the quest with this NPC
    protected $targetOfQuests;

    // NPCs may 'train' a user, giving them a profession.
    protected $hasProfessions;

    /**
     *  This is the main constructor, but I probably need a function to check if there are any NPCs
     *  in the current map region as well
     * @param int $npcId - Id of the NPC from the npcs table
     * @throws InvalidNpcException
     */
    public function __construct($arg)
    {

        if(is_int($arg)) {
            $npcId = $arg;
            // Check that npc exists and throw exception if not.
            $sql = "SELECT n.id, n.name, map_id, y_top, x_right, y_bottom, x_left,
            level, n.npc_icon_id, i.name AS icon_name,
            (SELECT 1 FROM npc_items WHERE n.id=n.id LIMIT 1) AS has_items, -- about half
            (SELECT 1 FROM quests WHERE giver_npc_id=n.id LIMIT 1) AS gives_quests, -- about 2/3
            (SELECT 1 FROM quests WHERE target_npc_id=n.id OR (giver_npc_id=n.id AND target_npc_id IS NULL) LIMIT 1) AS target_of_quests, -- about 1/3
            (SELECT 1 FROM npc_professions WHERE npc_id=n.id LIMIT 1) AS has_professions, -- about 1/3
            (SELECT 1 FROM npc_portals WHERE npc_id=n.id LIMIT 1) AS has_portals -- about 1/20
            FROM npcs n
            JOIN npc_icons i ON n.npc_icon_id=i.id
            WHERE n.id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$npcId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new InvalidNpcException($npcId);

            $arg = $stmt->fetch();

        } else if (!is_array($arg)) {
            throw new InvalidNpcException();
        }
        $this->id = (int)$arg['id'];
        $this->name = $arg['name'];
        $this->mapId = (int)$arg['map_id'];
        $this->yMax = max((int)$arg['y_top'],(int)$arg['y_bottom']);
        $this->yMin = min((int)$arg['y_top'],(int)$arg['y_bottom']);
        $this->xMax = max((int)$arg['x_left'],(int)$arg['x_right']);
        $this->xMin = min((int)$arg['x_left'],(int)$arg['x_right']);
        $this->level = (int)$arg['level'];
        $this->iconId = (int)$arg['npc_icon_id'];
        $this->hasItems = ($arg['has_items']==1);
        $this->givesQuests = ($arg['gives_quests']==1);
        $this->targetOfQuests = ($arg['target_of_quests']==1);
        $this->hasProfessions = ($arg['has_professions']==1);
        $this->hasPortals = ($arg['has_portals']==1);

        $this->setImageFilename();

        // Calculate probability of the NPC showing on any particular tile they live on
        $area = (1+abs(($this->yMax-$this->yMin))) * (1+abs(($this->xMax-$this->xMin)));
        $this->tileProbability=round(1/$area*100);

        // Adjust probability of npc being present by a factor. Previously the game multiplied it by 2.
        // Perhaps this makes sense to be a variable particular to each NPC, depending on how easy or hard
        // you want them to be to find
        $this->tileProbability*=2;


        $this->npcPortals = array();
        if ($this->hasPortals) {
            $sql = "SELECT * FROM npc_portals WHERE npc_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);

            // Create array of NpcPortal classes
            // Building class is extended to an npc_portal class?
            while  ($row = $stmt->fetch()) {
                array_push($this->npcPortals, new NpcPortal($row));
            }
        }
    } // end constructor

    /**
     * setImageFilename()
     * This is run on construction 
     */
    protected function setImageFilename() {
        $filename = Config::IMAGE_PATH;
        if (is_integer($this->iconId) && $this->iconId > 0) {
            $filename.='/npc/npc'.sprintf('%04d', $this->iconId);
            // If the client supports webp, use that instead
            if ( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false || !empty($_COOKIE['useWebP']) )
            {
                $filename.='.webp';
            } else {
                $filename.='.png';
            }
        } else {
            // Use a generic icon as a fallback
            $filename.= '/npc/npc0235.png';
        }
       $this->imageFilename = $filename;
    }
    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    public function getMapId() {return $this->mapId;}
    // Never ever use these, it will lead to much pain. Only check isNpcFoundAt(mapId, x, y)
    // public function getYMax() {return $this->yMax;}
    // public function getYMin() {return $this->yMin;}
    // public function getXMax() {return $this->xMax;}
    // public function getXMin() {return $this->xMin;}
    public function getLevel() {return (int)$this->level;}
    public function getImageFilename() {return $this->imageFilename;}
    public function getTileProbability() {return $this->tileProbability;}
    public function getNpcPortals() {return $this->npcPortals;}


    /**
     * isNpcFoundAt - a sanity check to ensure the NPC can be found at a given location.
     * This is used to ensure that the player is allowed to perform actions
     * involving an NPC
     * @param int $mapId
     * @param int $x
     * @param int $y
     * @return bool
     */
    public function isNpcFoundAt(int $mapId, int $x, int $y):bool {
        return $this->mapId == $mapId // On the same map
            && $x <= $this->xMax && $x >= $this->xMin // This x is within the NPC's X range
            && $y <= $this->yMax && $y >= $this->yMin; // This y is within the NPC's Y range
    }

    /**
    * getClientNPCData: Returns essential NPC data for client-side interaction
    */
    public function getClientNPCData() {
        $npcData = array();
        $npcData["id"] = $this->id;
        $npcData["name"] = $this->name;
        $npcData["level"] = $this->level;
        $npcData["probability"] = $this->tileProbability;
        $npcData["image"] = $this->imageFilename;
        // add array of npcPortals
        $npcData["portals"] = array();
        foreach ($this->npcPortals as $portal) {
            array_push($npcData["portals"], $portal->getClientNPCPortalData());
        }
        // add array of professions
        return $npcData;
    }

    /**
    * getItemsForPlayer: Returns array of NPCitem objects that are applicable to a player.
    * Shows both quest items and items for sale in the same data set. The difference is that
    * "for sale" items have NULL quest_id
    * @param $playerId int
    * @return array
    */
    public function getItemsForPlayer(int $playerId): array
    {
        $npcItems = array();
        if ($this->hasItems) {
            $sql = " SELECT ni.npc_id, ni.quest_id, ni.item_id AS id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, i.name AS icon_name
            FROM npc_items ni
            JOIN items i ON i.id=ni.item_id
            LEFT JOIN item_categories ic ON i.item_category_id=ic.id
            JOIN item_icons ico ON i.item_icon_id=ico.id
            -- These only show up if the player has the relevant quest and hasn't reported it
            JOIN player_quests pq ON ni.quest_id=pq.quest_id
            -- Only show if the player doesn't already have the item
            WHERE pq.player_id = ? AND ni.item_id NOT IN (SELECT item_id FROM player_items pi WHERE player_id = ? )
            AND ni.npc_id = ? AND (pq.success_time IS NULL OR pq.success_time=0)
            UNION ALL -- quest_id will be null, in that case we know these are items for sale by the NPC, show BUY prompt
            SELECT ni.npc_id, ni.quest_id, ni.item_id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, i.name AS icon_name
            FROM npc_items ni
            JOIN items i ON i.id=ni.item_id
            LEFT JOIN item_categories ic ON i.item_category_id=ic.id
            JOIN item_icons ico ON i.item_icon_id=ico.id
            WHERE quest_id IS NULL AND npc_id= ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$playerId, $playerId, $this->id, $this->id]);

            while ($row = $stmt->fetch()) {
                array_push($npcItems, new NpcItem($row));
            }
        }   

        return $npcItems;
    }

    /**
     * getItemsForQuest: Returns array of NPCitem objects that are applicable to a quest.
     * This is used to add the items to the player's inventory when they accept a quest
     * @param $questId int
     */
    public function getItemsForQuest(int $questId): array
    {
        $npcItems = array();
        if ($this->hasItems) {
            $sql = " SELECT ni.npc_id, ni.quest_id, ni.item_id AS id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, i.name AS icon_name
            FROM npc_items ni
            JOIN items i ON i.id=ni.item_id
            LEFT JOIN item_categories ic ON i.item_category_id=ic.id
            JOIN item_icons ico ON i.item_icon_id=ico.id
            WHERE ni.npc_id = ? AND ni.quest_id = ?; ";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id, $questId]);

            while ($row = $stmt->fetch()) {
                array_push($npcItems, new NpcItem($row));
            }
        }
        return $npcItems;
    }

    /**
    * getTargetQuestsForPlayer gets the PlayerQuests for which the player can report and answer quests.
    * This means the player has accepted this quest and meets whatever prerequisites there are.
    *
    * Returns array of PlayerQuests
    * @param $playerId
    */
    public function getTargetQuestsForPlayer(int $playerId) {
        $targetQuests = array();
        if ($this->targetOfQuests) {
            // This should create a list of PlayerQuests, not just quests.
            $sql = "SELECT pq.player_id, pq.pickup_time, pq.success_time, q.id, q.name, repeatable, repeatable_cd_in_minute,
           level, giver_npc_id, target_npc_id, prologue, content, target_npc_prologue, npc_has_quest_item,
           quest_result_type_id, success_words, failure_words, cd_in_minute, result, result_map_id, result_x, result_y,
           base_reward_automark_percentage, reward_profession_xp, reward_xp, reward_money, waiting_words,
           qrt.name AS quest_result_type_name, qrt.uses_items, qrt.uses_bool, qrt.uses_string, qrt.uses_answers, qrt.uses_variables, qrt.uses_automark
                FROM quests q
                JOIN quest_result_types qrt ON qrt.id=q.quest_result_type_id
                JOIN player_quests pq ON q.id=pq.quest_id
                WHERE pq.player_id= ? AND ((giver_npc_id = ? AND q.target_npc_id IS NULL)
                OR target_npc_id= ?)
                AND (pq.success_time IS NULL OR pq.success_time=0)
                ";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$playerId, $this->id, $this->id]);

            while ($row = $stmt->fetch()) {
                array_push($targetQuests, new PlayerQuest($row));
            }            
        }

        return $targetQuests;
    }


    /**
    * getQuestsForPlayer gets the quests that the player is eligible to accept.
    * This means the player and meets all quest prerequisites.
    * @param $playerId
    */
    public function getQuestsForPlayer(int $playerId) {
        $quests = array();
        if ($this->givesQuests) {
            $sql = "SELECT q.id, q.name, repeatable, repeatable_cd_in_minute,
                   level, giver_npc_id, target_npc_id, prologue, content, target_npc_prologue, npc_has_quest_item,
                   quest_result_type_id, success_words, failure_words, cd_in_minute, result, result_map_id, result_x, result_y,
                   base_reward_automark_percentage, reward_profession_xp, reward_xp, reward_money, waiting_words,
                   qrt.name AS quest_result_type_name, qrt.uses_items, qrt.uses_bool, qrt.uses_string, qrt.uses_answers, qrt.uses_variables, qrt.uses_automark
            FROM quests q
            JOIN quest_result_types qrt ON qrt.id=q.quest_result_type_id
            -- For the specific NPC in question
            WHERE giver_npc_id = ?
            -- player hasn't already taken the quest and not yet finished it
            AND q.id NOT IN (SELECT quest_id FROM player_quests pq WHERE pq.player_id = ? AND success_time IS NULL)
            -- player has all prerequisite quests
            AND (SELECT COUNT(*) FROM quest_prerequisite_quests WHERE quest_id=q.id)=(SELECT COUNT(DISTINCT quest_id) FROM player_quests_log WHERE quest_id IN (SELECT prerequisite_quest_id FROM quest_prerequisite_quests WHERE quest_id=q.id) AND quest_event_id=1 AND player_id = ?)
            -- player has all prerequisite professions and at the required level
            AND (SELECT COUNT(*) FROM quest_prerequisite_professions WHERE quest_id=q.id)=(SELECT COUNT(*) FROM player_professions pp WHERE pp.profession_id IN (SELECT qpp.profession_id FROM quest_prerequisite_professions qpp WHERE qpp.quest_id = q.id)
            -- player's profession level is at least as high as the quest_level
            AND pp.profession_level >= q.level AND pp.player_id = ?)
            -- Eliminate quests where Player has completed the quest (SUCCESS/FAILED) 
            -- and it is not repeatable OR the cooldown time has not yet elapsed
            AND q.id NOT IN (
                SELECT pql.quest_id FROM player_quests_log pql
                WHERE pql.quest_id=q.id AND pql.player_id = ?
                AND (quest_event_id = 1 AND (q.repeatable=0 OR q.repeatable_cd_in_minute > TIMESTAMPDIFF(MINUTE, pql.event_datetime, NOW()) )
                    OR (quest_event_id = 2 AND q.cd_in_minute > TIMESTAMPDIFF(MINUTE, pql.event_datetime, NOW()))
                )
            );";
            // The 1 and 2 above should be QuestEvents::SUCCESS, QuestEvents::FAILED, but that messes up syntax highlighting in PhpStorm

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id, $playerId, $playerId, $playerId, $playerId]);

            while ($row = $stmt->fetch()) {
                array_push($quests, new Quest($row));
            }
        }

        return $quests;
    }


    /**
    * getProfessions for player gets professions that this NPC can train if the player isn't already this profession.
    * To match the previous implementation, it looks like I can check the player's prerequisites after they attempt to join.
    * This at least allows the player to see that the profession is offered and find out what is required.
    * @param $playerId
    */
    public function getProfessionsForPlayer(int $playerId) {
        $professions = array();
        if ($this->hasProfessions) {
            $sql = "SELECT *,
            (SELECT 1 FROM profession_prerequisites pr WHERE pr.profession_id=p.id LIMIT 1) AS has_prerequisite
            FROM npc_professions np
            JOIN professions p ON p.id=np.profession_id
            WHERE np.profession_id NOT IN (SELECT profession_id FROM player_professions WHERE player_id=?)
            AND npc_id=?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$playerId, $this->id]);

            while ($row = $stmt->fetch()) {
                array_push($professions, new Profession($row));
            }
        }
        return $professions;
    }


} // end class Npc
