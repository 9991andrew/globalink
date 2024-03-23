<?php

/**
 * Quests may be accepted by a Player who may then attempt complete them by meeting various requirements.
 * There are many properties of quests and there are many "quest result types" which each have different
 * requirements. The differences between quest result types are implemented in the PlayerQuest class.
 */
class Quest extends Dbh {

    protected $id;
    protected $name;
    protected $repeatable;
    protected $repeatCooldownMins; // How long must a player wait before redoing the quest?
    // protected $questOpportunity; // no idea, I guess the chance of the quest showing? It's not used in the game
    protected $level; // player must have this level of a required profession to take the quest

    protected $giverNpcId;
    protected $targetNpcId;
    protected $prologue;
    protected $content;
    protected $targetNpcPrologue;
    protected $npcHasQuestItem; // Could probably also determine this from the query
    protected $resultTypeId;
    protected $resultType;
    protected $successWords;
    protected $failureWords;
    protected $waitingWords;
    protected $cooldownMins; // I think this is how long a player must wait to try again if they get the answer wrong 
    protected $result; // No longer used
    protected $resultMapId;
    protected $resultX;
    protected $resultY;
    protected $baseRewardAutomarkPercentage;
    protected $rewardProfessionXP;
    protected $rewardXP;
    protected $rewardMoney;
    // Array of prerequisiteProfessionIds
    protected $prerequisiteProfessionIds;
    // Array of questItems objects
    protected $questItems;
    // Array of questTools objects
    protected $questTools;
    // Array of QuestVariables objects
    protected $questVariables;
    // Array of QuestAnswers objects
    protected $questAnswers;
    // Array of rewardItems objects
    protected $rewardItems;

    // Flags for various data features
    protected $usesItems;
    protected $usesBool;
    protected $usesString;
    protected $usesAnswers;
    protected $usesVariables;
    protected $usesQuestions;
    protected $usesAutomark;

    /**
     * getQuestResultTypes() returns array of quest result types.
     */
    public static function getQuestResultTypes() {
        $sql = "SELECT * FROM quest_result_types ORDER BY id ASC";
        $dbh = new Dbh();
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();
        $dbh->connection = null;
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Quest constructor. Needs quest_id or array of query columns
     * @param arg - either id (questId) or an array containing all item values from an SQL query
     * @throws Exception
     */
    public function __construct($arg)
    {

        if(is_int($arg)) {

            $this->id=$arg;
            // Look up information about given quest.

            // Check that item exists and throw exception if not.
            $sql = "SELECT q.id, q.name, repeatable, repeatable_cd_in_minute,
       level, giver_npc_id, target_npc_id, prologue, content, target_npc_prologue, npc_has_quest_item,
       quest_result_type_id, success_words, failure_words, cd_in_minute, result, result_map_id, result_x, result_y,
       base_reward_automark_percentage, reward_profession_xp, reward_xp, reward_money, waiting_words,
       qrt.name AS quest_result_type_name, qrt.uses_items, qrt.uses_bool, qrt.uses_string, qrt.uses_answers, qrt.uses_variables, qrt.uses_automark
            FROM quests q
                JOIN quest_result_types qrt ON qrt.id=q.quest_result_type_id
                WHERE q.id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidQuestException($this->id);
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidItemException();
        }

        $this->id = (int)$arg['id'];
        $this->name = $arg['name'];
        $this->repeatable = $arg['repeatable'] == 1;
        $this->repeatCooldownMins = (int)$arg['repeatable_cd_in_minute'];
        $this->level = (int)$arg['level'];
        $this->giverNpcId = (int)$arg['giver_npc_id'];
        $this->targetNpcId = (is_null($arg['target_npc_id'])?$this->giverNpcId:(int)$arg['target_npc_id']);
        $this->prologue = $arg['prologue'];
        $this->content = $arg['content'];
        $this->targetNpcPrologue = $arg['target_npc_prologue'];
        $this->npcHasQuestItem = $arg['npc_has_quest_item'] == 1;
        $this->resultTypeId = (int)$arg['quest_result_type_id'];
        $this->resultType = $arg['quest_result_type_name'];
        $this->successWords = $arg['success_words'];
        $this->failureWords = $arg['failure_words'];
        $this->waitingWords = $arg['waiting_words'];
        $this->cooldownMins = (int)$arg['cd_in_minute'];
        $this->result = $arg['result'];
        $this->resultMapId = (int)$arg['result_map_id'];
        // resultX and resultY are formulas, so we keep them as text
        $this->resultX = $arg['result_x'];
        $this->resultY = $arg['result_y'];
        $this->baseRewardAutomarkPercentage = (int)$arg['base_reward_automark_percentage'];
        $this->rewardProfessionXP = (int)$arg['reward_profession_xp'];
        $this->rewardXP = (int)$arg['reward_xp'];
        $this->rewardMoney = (int)$arg['reward_money'];
        $this->usesItems = $arg['uses_items']==1;
        $this->usesBool = $arg['uses_bool']==1;
        $this->usesString = $arg['uses_string']==1;
        $this->usesAnswers = $arg['uses_answers']==1;
        $this->usesVariables = $arg['uses_variables']==1;
        $this->usesAutomark = $arg['uses_automark']==1;

        // Get rewarditems that are given to the player for completing the quest.
        $sql = "SELECT qri.quest_id, qri.item_amount, i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, ico.name AS icon_name FROM quest_reward_items qri
                LEFT JOIN items i ON i.id=qri.item_id
                LEFT JOIN item_categories ic ON ic.id=i.item_category_id
                LEFT JOIN item_icons ico ON ico.id=i.item_icon_id
                WHERE quest_id = ?;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);

        $this->rewardItems = array();
        while ($row = $stmt->fetch()) {
            array_push($this->rewardItems, new RewardItem($row));
        }

        // Get questItems and populate the $this->questItems array
        $sql = "SELECT qi.quest_id, qi.item_amount, qi.answer_bool, qi.answer_seq, qi.answer_string,
                i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, ico.name AS icon_name FROM quest_items qi
                LEFT JOIN items i ON i.id=qi.item_id
                LEFT JOIN item_categories ic ON ic.id=i.item_category_id
                LEFT JOIN item_icons ico ON ico.id=i.item_icon_id   
                WHERE quest_id = ?
                -- By default we order by the answer_seq, although sort quests are shuffled later.
                ORDER BY qi.answer_seq ASC;";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);

        $this->questItems = array();
        while ($row = $stmt->fetch()) {
            array_push($this->questItems, new QuestItem($row));
        }

        // Randomize the quest items for type 5 (order) so the player can't just guess based on the order they started in
        if ($this->resultTypeId == 5) {
            shuffle($this->questItems);
        }

        $this->prerequisiteProfessionIds = array();
        $sql = "SELECT profession_id FROM quest_prerequisite_professions WHERE quest_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);
        // Add ints from query to array
        $this->prerequisiteProfessionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $this->questVariables = array();
        // Create array of variables used by quest
        if ($this->usesVariables) {
            // Create all the variables objects
            $sql = "SELECT * FROM quest_variables WHERE quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);

            while ($row = $stmt->fetch()) {
                array_push($this->questVariables, new QuestVariable($row));
            }
        }

        $this->questAnswers = array();
        // Create array of answers used by quest
        if ($this->usesAnswers) {
            // Create all the answers objects
            $sql = "SELECT * FROM quest_answers WHERE quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);

            while ($row = $stmt->fetch()) {
                array_push($this->questAnswers, new QuestAnswer($row));
            }
        }
    }


    public function getId() {return $this->id;}
    public function getName() {return $this->name;}

    /**
     * @return bool
     */
    public function isRepeatable(): bool
    {
        return $this->repeatable;
    }

    public function getRepeatCooldownMins()
    {
        return $this->repeatCooldownMins;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getGiverNpcId()
    {
        return $this->giverNpcId;
    }

    public function getTargetNpcId()
    {
        return $this->targetNpcId;
    }

    public function getPrologue()
    {
        return $this->prologue;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getTargetNpcPrologue()
    {
        return $this->targetNpcPrologue;
    }

    /**
     * @return bool
     */
    public function isNpcHasQuestItem(): bool
    {
        return $this->npcHasQuestItem;
    }

    public function getResultTypeId()
    {
        return $this->resultTypeId;
    }

    public function getResultType()
    {
        return $this->resultType;
    }

    public function getSuccessWords()
    {
        return $this->successWords;
    }

    public function getFailureWords()
    {
        return $this->failureWords;
    }

    public function getWaitingWords()
    {
        return $this->waitingWords;
    }

    public function getCooldownMins()
    {
        return $this->cooldownMins;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function getPrerequisiteProfessionIds()
    {
        return $this->prerequisiteProfessionIds;
    }

    /**
     * Used in locations.
     * @return mixed
     */
    public function getResultMapId()
    {
        return $this->resultMapId;
    }
    public function getResultX()
    {
        return $this->resultX;
    }
    public function getResultY()
    {
        return $this->resultY;
    }

    public function getBaseRewardAutomarkPercentage()
    {
        return $this->baseRewardAutomarkPercentage;
    }

    public function getRewardProfessionXP()
    {
        return $this->rewardProfessionXP;
    }

    public function getRewardXP()
    {
        return $this->rewardXP;
    }

    public function getRewardMoney()
    {
        return $this->rewardMoney;
    }

    /**
     * @return array
     */
    public function getRewardItems(): array
    {
        return $this->rewardItems;
    }

    /**
     * @return array
     */
    public function getQuestItems(): array
    {
        return $this->questItems;
    }

    /**
     * @return array
     */
    public function getQuestVariables(): array
    {
        return $this->questVariables;
    }

    /**
     * @return array
     */
    public function getQuestAnswers(): array
    {
        return $this->questAnswers;
    }


}

