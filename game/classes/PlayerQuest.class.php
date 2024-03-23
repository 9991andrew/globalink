<?php

/**
 * Extends Quest, adding information about the Player's pickup and report time.
 * Has methods to render HTML for quest quizzes and check submitted answers from Players.
 * This is the second largest class in MEGA World.
 */
class PlayerQuest extends Quest {
    protected $playerId;
    protected $pickupTime;
    protected $successTime;
    // This should be an associative array. I don't think this warrants a whole class..
    // I'm so lazy about making new classes after being spoiled by Eloquent
    protected $variables;
    protected $playerQuestAnswers;

    /**
     * PlayerQuest constructor. Needs a quest_id and player_id
     * @param $arg - Either the quest Id or a query row with all quest data.
     * @param int $playerId - Only required when instantiating with a quest_id.
     * @throws Exception
     */
    public function __construct($arg, int $playerId=null) {
        if (is_int($arg)) {
            $questId = $arg;

            if (is_null($playerId)) {
                throw new Exception('Instantiating a PlayerQuest with a questId requires a playerId.');
            }
            // Run query to get a playerQuest
            // Check that item exists and throw exception if not.
            $sql = "SELECT pq.player_id, pq.pickup_time, pq.success_time, q.id, q.name, repeatable, repeatable_cd_in_minute,
               level, giver_npc_id, target_npc_id, prologue, content, target_npc_prologue, npc_has_quest_item,
               quest_result_type_id, success_words, failure_words, cd_in_minute, result, result_map_id, result_x, result_y,
               base_reward_automark_percentage, reward_profession_xp, reward_xp, reward_money, waiting_words,
               qrt.name AS quest_result_type_name, qrt.uses_items, qrt.uses_bool, qrt.uses_string, qrt.uses_answers, qrt.uses_variables, qrt.uses_automark
                    FROM player_quests pq
                    JOIN quests q ON q.id=pq.quest_id
                    JOIN quest_result_types qrt ON qrt.id=q.quest_result_type_id
                    WHERE pq.quest_id = ? AND pq.player_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$questId, $playerId]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new Exception("Quest $questId not found for player $playerId.");
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
        // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new Exception('Invalid argument passed to PlayerQuest constructor. QuestId and PlayerId or queryRow required.');
        }

        // Create the base item
        parent::__construct($arg);

        $this->playerId = (int)$arg['player_id'];
        $this->pickupTime = $arg['pickup_time'];
        $this->successTime = $arg['success_time'];

        // Create any variables that are used for this quest
        $this->variables = array();
        if ($this->usesVariables) {
            $sql = "SELECT * FROM player_quest_variables WHERE player_id = ? AND quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->playerId, $this->id]);
            while ($row = $stmt->fetch()) {
                $this->variables[$row['var_name']] = $row['var_value'];
            }
        }

        $this->playerQuestAnswers = array();
        // Create array of answers used by quest.
        // This is very similar to the constructor used in quests for questAnswers
        // except that it also gets passed a playerId and the variables array.
        if ($this->usesAnswers || $arg['quest_result_type_id'] == 16) {
            // Create all the answers objects
            $sql = "SELECT * FROM quest_answers WHERE quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);

            while ($row = $stmt->fetch()) {
                array_push($this->playerQuestAnswers, new PlayerQuestAnswer($row, $this->playerId, $this->variables));
            }
        }

        // Replace the quest content with the variables or answers.
        if (sizeof($this->variables)) {
            $this->content =  Helpers::injectVariables($this->content, $this->variables);
        } else if (sizeof($this->playerQuestAnswers)) {
            // This is only used for Cloze quests to add blanks into the content for the player to fill in.
            // Make an associative array of answers for the injectVariables function
            $answers = array();
            foreach($this->playerQuestAnswers as $answer) {
                // We make the input a little wider if the correct answer is over 9 characters.
                $widthClass = 'w-32';
                if (strlen($answer->getAnswerString()) > 9) {
                    $widthClass = 'w-44';
                }
                // I could make this a prefixed field that shows the field name. It's just used for the injection.
                $answers[$answer->getName()] = '<input type="text" id="answer-'.$answer->getId().'" name="answer-'.$answer->getId().'" class="'.$widthClass.' py-0.5 px-1 inline-block m-0.5" />';
            }

            $this->content =  Helpers::injectVariables($this->content, $answers);
        }

    } // end constructor

    /**
     * @return mixed
     */
    public function getPlayerId()
    {
        return $this->playerId;
    }

    /**
     * @return mixed
     */
    public function getPickupTime()
    {
        return $this->pickupTime;
    }

    /**
     * @return mixed
     */
    public function getSuccessTime()
    {
        return $this->successTime;
    }

    /**
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * Checks that the player is ready to complete the request. If not, returns the reason for it.
     * An empty string as a return value means that the player is ready.
     * @return string
     */
    public function getReasonPlayerNotReady():string
    {
        // We have to check if the player has attempted completing this before.
        // If yes, we have to ensure the requisite cooldown time has elapsed.
        // If the player successfully completed this, we check that the quest is repeatable.
        if (!is_null($this->successTime)) {
            // Check the player's last successful completion and last failure.
            $sql = "SELECT (
            SELECT TIMESTAMPDIFF(MINUTE,event_datetime,NOW()) FROM player_quests_log
            WHERE player_id = ? AND quest_id = ? AND quest_event_id = 1
            ORDER BY event_datetime DESC LIMIT 1) AS last_success,
            (SELECT TIMESTAMPDIFF(MINUTE,event_datetime,NOW()) FROM player_quests_log
            WHERE player_id = ? AND quest_id = ? AND quest_event_id = 2
            ORDER BY event_datetime DESC LIMIT 1) AS last_failure";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->playerId, $this->id, $this->playerId, $this->id]);
            $row = $stmt->fetch();
            $minsSinceSuccess = $row['last_success'];
            $minsSinceFail = $row['last_fail'];

            if (!$this->repeatable && !is_null($minsSinceSuccess)) {
                return _("This quest has already been completed and is not repeatable.");
            } else if ($this->repeatable && !is_null($minsSinceSuccess) && $minsSinceSuccess < $this->repeatCooldownMins) {
                return sprintf(ngettext("You must wait one minute before repeating this quest.",
                    "You must wait another %d minutes before repeating this quest.", ($this->repeatCooldownMins - $minsSinceSuccess)),
                    ($this->repeatCooldownMins - $minsSinceSuccess));
            } else if (!is_null($minsSinceFail) && $minsSinceFail < $this->cooldownMins) {
                return sprintf(ngettext("You must wait one minute before attempting to complete this quest again.",
                    "You must wait another %d minutes before attempting to complete this quest again.", ($this->cooldownMins - $minsSinceFail)),
                    ($this->cooldownMins - $minsSinceFail));
            }
        }

        // Now check that the player has the required quest items.
        $sql = "SELECT qi.item_id, qi.item_amount, pi.bag_slot_amount FROM quest_items qi
        LEFT JOIN player_items pi ON qi.item_id=pi.item_id AND pi.player_id = ? OR pi.player_id IS NULL
        WHERE qi.quest_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->playerId, $this->id]);
        $missingItems = 0;
        while ($row = $stmt->fetch()) {
            // Checks that the player has enough of all the required items.
            // If they have insufficient of an item, $missingItems is incremented.
            if (is_null($row['bag_slot_amount']) || $row['item_amount'] > $row['bag_slot_amount']) {
                $missingItems++;
            }
        }
        if ($missingItems > 0) return _("It seems you don't have everything you need yet.").'<br>'.ngettext("You are missing an item.", "You are missing %d items.", $missingItems);
        // TODO: Optionally list the items here as in the player's quest view.

        $playerBag = new PlayerBag($this->playerId);
        // Count slots required by reward items:
        // Take the amount into consideration for items that don't stack.
        $slotsRequired = 0;
        foreach($this->rewardItems as $rewardItem) {
            $slotsRequired += ceil($rewardItem->getAmount()/$rewardItem->getMaxAmount());
        }
        // DEBUG:
        // return "You will be given $slotsRequired slots of rewards, ".$this->countOwnedQuestItems()." slots will be freed, you currently have ".$playerBag->getAvailableSlots();

        // Check that the player has enough free space in their bag to complete the quest (and receive reward items).
        $additionalSlotsRequired = $slotsRequired - $this->countOwnedQuestItems() - $playerBag->getAvailableSlots();

        if ($additionalSlotsRequired > 0) {
            return sprintf(ngettext("You need to free up a slot",
                "You need to free up %d slots", $additionalSlotsRequired), $additionalSlotsRequired) .
                " "._("in your bag before you can complete this quest.");
        }

        // If nothing is wrong return an empty string.
        return "";
    }

    /**
     * getClientPlayerQuestData: Returns essential quest data for client-side interaction.
     * This will show up in the Quests panel in map.php.
     */
    public function getClientPlayerQuestData() {
        $playerQuestData = array();
        $playerQuestData["qid"] = $this->id;
        $playerQuestData["name"] = $this->name;
        $playerQuestData["pu"] = $this->pickupTime;
        $playerQuestData["lr"] = $this->successTime;
        return $playerQuestData;
    }

    /**
     * Removes a quest from the player's list of quests and logs that the quest was dropped.
     */
    public function drop() {
        $player = new Player($this->playerId, true);
        // Drop the variables the player has for this quest
        $this->dropQuestVariables();
        // Safe drop the quest items so they only lose items strictly associated with the quest.
        $this->dropQuestItems( true);

        $sql = "DELETE FROM player_quests WHERE player_id = ? AND quest_id = ?;";
        // Note here that I'm inserting NULL for the npc_id as they aren't involved in dropping the quest, really.
        // Historically this seemed to be the quest_giver_npc_id in this field.
        // I don't really want to instantiate a class or complicate this method by requiring an NPC to be added here.
        $sql.= "INSERT INTO player_quests_log (player_id, quest_id, event_datetime, quest_event_id, npc_id, map_id, x, y)
        VALUES(?, ?, NOW(), ?, NULL, ?, ?, ?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->playerId, $this->id,
            $this->playerId, $this->id, QuestEvents::DROP, $player->getMapId(), $player->getX(), $player->getY()]);

        // Ensure there is nothing in the automarking table related to this quest.
        if ($this->resultTypeId == 11 || $this->resultTypeId == 14 || $this->resultTypeId == 16) {
            $sql = "SELECT qaa.id FROM quest_answer_automarks qaa, quest_answers qa " .
                 "WHERE qaa.question_id = qa.id " .
                 "AND qa.quest_id = ? " .
                 "AND qaa.player_id = ?;";

            if ($this->resultTypeId == 16) {
                $sql = "SELECT id FROM quest_answer_automarks " .
                     "WHERE question_id = ? " .
                     "AND player_id = ?;";
            }
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id, $this->playerId]);
            $markIds = [];
            while ($row = $stmt->fetch()) {
                $markIds[] = $row[0];
            }
            if (count($markIds)) {
                $this->deleteOldAutomarks($markIds);
            }
        }
        // Not much point in doing this, player will be refreshed the next time it's used.
        // This could be called from wherever drop() is called.
        // $this->player->refreshPlayerQuests();
    }

    /**
     * Drops all variables that the player has which are required by a certain quest.
     */
    public function dropQuestVariables()
    {
        // Typically used when a player drops a quest
        $sql = "DELETE FROM player_quest_variables WHERE player_id = ? AND quest_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->playerId, $this->id]);
    }

    /**
     * Return the number of items the player has for this quest that will be dropped when they complete the quest.
     * This is used to determine if they have enough inventory room to complete a quest.
     * @param $safeDrop bool only counts items that were explicitly given for this quest.
     * @return int
     *
     * FIXME: Currently the player drops ALL items that have the ID of a quest item. They should only drop the quantity needed for the quest.
     */
    public function countOwnedQuestItems($safeDrop=false): int
    {
        // Drops items associated with this quest
        if ($safeDrop) {
            // Typically used when a player drops a quest
            $sql = "SELECT COUNT(*) FROM player_items WHERE player_id = ? AND quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->playerId, $this->id]);
            return $stmt->fetchColumn();
        } else {
            // This is typically used when the quest is successfully completed. Will drop quest items, quest tools,
            // and items received specifically for the quest.
            // This may drop items we want to keep, I'm not sure about this yet.
            $sql = "SELECT COUNT(*) FROM player_items WHERE player_id = ?
                   AND (
                       (item_id IN (SELECT item_id FROM quest_items WHERE quest_id = ? UNION SELECT item_id FROM quest_tools WHERE quest_id = ?)
                        AND (quest_id = ? OR quest_id IS NULL))
                       OR quest_id = ?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([
                $this->playerId,
                $this->id, $this->id,
                $this->id,
                $this->id,
            ]);
            return $stmt->fetchColumn();
        }
    }

    /**
     * Drops all items that the player has which are required by (or used as tools for) a certain quest.
     * This will not drop items that were specifically recorded as being received for another quest,
     * which should prevent multiple concurrent quests using the same item_id from being able to drop each other's items.
     *
     * Note: If this is used it's possible the player may not be able to get the items again.
     *
     * @param $safeDrop bool only drops items that were explicitly given for this quest.
     * Setting safedrop to true Makes it less likely player will lose items they cannot get back by re-accepting the quest.
     *
     * FIXME: Currently the player drops ALL items that have the ID of a quest item. They should only drop the quantity needed for the quest.
     */
    public function dropQuestItems($safeDrop=false)
    {
        // Drops items associated with this quest
        if ($safeDrop) {
            // Typically used when a player drops a quest
            $sql = "DELETE FROM player_items WHERE player_id = ? AND quest_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->playerId, $this->id]);
        } else {
            // This is typically used when the quest is successfully completed. Will drop quest items, quest tools,
            // and items received specifically for the quest.
            // This may drop items we want to keep, I'm not sure about this yet.
            $sql = "DELETE FROM player_items WHERE player_id = ?
                   AND (
                       (item_id IN (SELECT item_id FROM quest_items WHERE quest_id = ? UNION SELECT item_id FROM quest_tools WHERE quest_id = ?)
                        AND (quest_id = ? OR quest_id IS NULL))
                       OR quest_id = ?);";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([
                $this->playerId,
                $this->id, $this->id,
                $this->id,
                $this->id,
            ]);
        }

        // No point, player will be refreshed soon enough. Could call this from where quest is dropped
        // $this->refreshPlayerItems();
    }

    /**
     * Records a player as having failed an attempt at a quest.
     * This will display the NPC incorrect message and start the clock on the cooldown timer
     *
     * @return string
     */
    public function questFail(): string
    {
        $player = new Player($this->playerId, true);

        $sql = "INSERT INTO player_quests_log (player_id, quest_id, event_datetime, quest_event_id, npc_id, map_id, x, y)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([
            $this->playerId, $this->id, QuestEvents::FAILED, $this->targetNpcId,
            $player->getMapId(), $player->getX(), $player->getY()
        ]);

        $html = "";
        $html .= '<div class="managementContent">'.$this->failureWords.'</div>';

        return $html;
    }

    /**
     * Records a player's quest as having been successfully completed and updates the logs accordingly.
     * Displays player rewards should probably be returned from this.
     *
     * @return string
     */
    public function questSuccess(): string
    {
        $player = new Player($this->playerId);

        // Update this object's last report time.
        $sql = "UPDATE player_quests SET success_time=NOW() WHERE player_id = ? AND quest_id = ?;
            INSERT INTO player_quests_log (player_id, quest_id, event_datetime, quest_event_id, npc_id, map_id, x, y)
            VALUES (?, ?, NOW(), ?, ?, ?, ?, ?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([
            $this->playerId, $this->id,
            $this->playerId, $this->id, QuestEvents::SUCCESS, $this->targetNpcId,
            // These require the player object... hmm...
            $player->getMapId(), $player->getX(), $player->getY()
        ]);

        $this->successTime = date('Y-m-d H:i:s');

        $player->addExperience($this->rewardXP);
        $player->addMoney($this->rewardMoney);

        // Attempt to level up player in case they have sufficient profession XP.
        // Note: if the player does level up, this will return a message displayed along with quest rewards.
        // We are now only doing this for professions used by the quest -- but for quests with multiple professions,
        // the player gets points for all of those professions.
        $levelUpMessage = "";
        foreach($this->prerequisiteProfessionIds as $professionId) {
            $player->addProfessionXP($professionId, $this->rewardProfessionXP);
            $levelUpMessage .= $player->levelUp($professionId);
        }


        // Prepare message showing player rewards for completing the quest.
        $html = '';
        $html.='<div class="managementContent">'.$this->successWords.'</div>';
        // Show rewards
        $html .= '<div class="insetSection">';
        $html .= '<h3>'._("Reward").'</h3>';
        $html .= '<div class="mb-1 p-1">';
        if ($this->rewardMoney > 0)
            $html .= '<div class="flex items-center"><strong class="mr-3">'._("Money").' </strong> +$'.$this->rewardMoney.'</div>';
        if ($this->rewardXP > 0)
            $html .= '<div class="flex items-center"><strong class="mr-3">'._("Experience").' </strong> +'.$this->rewardXP.'</div>';
        if ($this->rewardProfessionXP > 0)
            $html .= '<div class="flex items-center"><strong class="mr-3">'._("Profession XP").' </strong> +'.$this->rewardProfessionXP.'</div>';
        $html .= '</div>';
        $html .= '</div>';//.insetSection

        // Delete quest items before receiving the reward items.
        $this->dropQuestItems();

        // Give the player any items that were awarded for completing the quest.
        foreach ($this->rewardItems as $rewardItem) {
            $player->addItem($rewardItem, $rewardItem->getAmount());
        }

        // Also delete any variables used by the quest for this player
        $this->dropQuestVariables();

        if (sizeof($this->rewardItems) > 0) {
            $html .= '<div class="insetSection">';
            $html .= '<h3>'._("Items Awarded").'</h3><div class="mb-1 px-1">';
            foreach($this->rewardItems as $rewardItem) {
                $html .= '<div class="flex items-center">';
                $html .= '<img class="h-6 mr-2" alt="'._("Item icon").'" src="'.$rewardItem->getImageFilename().'" />';
                $html .= $rewardItem->getName();
                if ($rewardItem->getAmount() > 1) {
                    $html .= '<strong class="mr-3">'._("Quantity").': </strong> '.$rewardItem->getAmount();
                }

                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';//.insetSection

            // Probably not strictly necessary, do it if I can
            $player->refreshPlayerItems();
        }

        // Add a levelUpMessage (if there was one, this is usually empty)
        $html.=$levelUpMessage;
        return $html;
    } // end questSuccess()

    /**
     * Next few functions related to automarking of long text and conversation
     * types quests.
     *
     * @return string
     */
    public function questWaiting(): string
    {
        return '<div class="managementContent">'.$this->waitingWords.'</div>';
    }

    /**
     * Returns an array of automarks for this quest and player.
     *
     * @return array
     */
    public function getAutomarks($useQuest): array
    {
        $sql = "SELECT qaa.id, qaa.question_id, qaa.automark FROM quest_answer_automarks qaa, quest_answers qa " .
             "WHERE qaa.question_id = qa.id " .
             "AND qa.quest_id = ? " .
             "AND qaa.player_id = ?";
        if (!$useQuest) {
            $sql = "SELECT * FROM quest_answer_automarks WHERE question_id=? AND player_id=?";
        }
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->getId(), $this->playerId]);
        $rows = [];
        while ($row = $stmt->fetch()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Returns the needed percentage to pass the quest.
     *
     * @return int
     */
    public function getBaseRewardAutomarkPercentage()
    {
        return $this->baseRewardAutomarkPercentage;
    }

    /**
     * Deletes automarks from the database after the player has either
     * succeeded or failed.
     *
     * @param array $markIds - The ids of the automarks to delete.
     */
    public function deleteOldAutomarks(array $markIds)
    {
        $n = '';
        foreach ($markIds as $mid) {
            $n .= '?, ';
        }
        $n = substr($n, 0, -2);
        $sql = "DELETE FROM quest_answer_automarks WHERE id IN (".$n.")";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute($markIds);
    }

    /**
     * Inserts a record into the quest_aswer_automarks table.
     *
     * @param int $questionID - The question ID.
     * @param int $playerID - The player's ID.
     * @param string $answer - The player's answer.
     */
    public function addToAutomarks($questionID, $playerID, $answer) {

        $sql = "INSERT INTO quest_answer_automarks (question_id, player_id, answer) VALUES (?, ?, ?);";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$questionID, $playerID, $answer]);

    }

    /**
     * Updates a record in the quest_aswer_automarks table.
     *
     * @param int $questionID - The question ID.
     * @param int $playerID - The player's ID.
     * @param number $mark - The player's mark.
     */
    public function updateAutomarks($questionID, $playerID, $mark) {

        $sql = "UPDATE quest_answer_automarks SET automark=? WHERE question_id=? AND player_id=?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$mark, $questionID, $playerID]);
    }

    /**
     * attemptToCompleteQuest completes simple quests or renders the HTML used to
     * display the quest content, typically a multiple choice or short-answer quiz
     * that a player fills out to pass the quest.
     *
     * This is a method of PlayerQuest (not Quest) because some resultTypes support
     * the use of player variables, where a player is randomly assigned values used
     * to evaluate the result of the quest, and thus those variables wouldn't be
     * available to a plain Quest object.
     *
     * Additionally, this method could check that a player does have any required
     * QuestItems in their inventory (although this check is currently already done
     * at another time).
     *
     * To add additional quest types, one could copy the most similar-looking quest
     * and add a new case/break for that quest. If it's complicated, I recommend
     * breaking out into a separate function to handle the logic.
     *
     * @return string
     */
    public function attemptToCompleteQuest(): string
    {
        // Form tag added to any quest that uses a form for user input (all except for coordinates)
        $formOpen =  '<form class="insetSection" onsubmit="checkAnswers(event, this)" method="post">';
        $formOpen .= '  <input type="hidden" name="questId" value="'.$this->id.'">';
        // Html for a submit button and form close tag
        $formClose =  '  <div class="text-center py-1"><button class="btn highlight" type="submit">'._("Submit Answer").'</button></div>';
        $formClose .= '</form>';

        $html = '<div>';

        switch ($this->resultTypeId) {
            // Check In should already be handled before this is called, but just in case,
            // we also handle them here.
            case 1: // Quest items
                $html .= $this->questSuccess();
                break;

            case 2: // Items True/False
            case 3: // Items Choose One
            case 4: // Items Choose Multiple
            case 5: // Items Order
            case 6: // Items Text Answers
            case 15: // Items Calculation
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                // Print the instructions for each type of quest
                $html .= '<div class="text-sm opacity-70">';
                if ($this->resultTypeId == 2) $html .= _('Specify true or false');
                if ($this->resultTypeId == 3) $html .= _('Choose one');
                if ($this->resultTypeId == 4) $html .= _('Choose any correct items');
                if ($this->resultTypeId == 5) $html .= _('Drag items into the correct order');
                if ($this->resultTypeId == 15) $html .= _('Calculate the correct values below');
                if ($this->resultTypeId == 6) $html .= ngettext('Type in the correct answer', 'Type in the correct answers', sizeof($this->questItems));
                $html .= '</div>';
                $html .= $this->getContentViewItemQuiz();
                $html .= $formClose;
                break;

            case 7: // Cloze (Fill in Blanks)
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">'._("Fill in the blanks").'</div>';
                $html .= '<div class="managementContent py-3">'.$this->getContent().'</div>';
                $html .= $formClose;
                break;

            case 8: // True/False
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '  <div class="text-sm opacity-70">'._("Specify true or false").'</div>';
                $html .= '  <ul class="mt-2">';

                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<li class="quizItem block">';
                    if (strlen($answer->getQuestion())) {
                        $html .= '<div class="managementContent w-full">'.$answer->getQuestion().'</div>';
                    }
                    $html .= '<div class="flex justify-center mb-1 space-x-2">';
                    // For localization, prefer showing a whole word over using T/F abbreviations in the displayed options.
                    $html .= '<label class="trueFalse" for="answer-'.$answer->getId().'-T"><input class="mr-1" type="radio" id="answer-'.$answer->getId().'-T" name="answer-'.$answer->getId().'" value="1">'._("True").'</label>';
                    $html .= '<label class="trueFalse" for="answer-'.$answer->getId().'-F"><input class="mr-1" type="radio" id="answer-'.$answer->getId().'-F" name="answer-'.$answer->getId().'" value="0">'._("False").'</label>';
                    $html .= '</div>';
                    $html .= '</li>';
                }

                $html .= '  </ul>';
                $html .= $formClose;
                break;

            case 9: // Choose one (radio buttons)
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">'._("Choose the correct option").'</div>';
                $html .= '<ul class="mt-2">';
                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<label class="group" for="answer-'.$answer->getId().'">';
                    $html .= '<li class="quizItem quizItemHover items-center">';
                    $html .= '  <input class="mr-2.5" type="radio" id="answer-'.$answer->getId().'" name="answer" value="'.$answer->getId().'">';
                    $html .= ' <div class="managementContent">'.$answer->getAnswerString().'</div>';
                    $html .= '</li>';
                    $html .= '</label>';
                }
                $html .= '</ul>';
                $html .= $formClose;
                break;

            case 10: // Choose multiple (checkboxes)
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">'._("Choose all correct options").'</div>';
                $html .= '<ul class="mt-2">';
                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<label class="group" for="answer-'.$answer->getId().'">';
                    $html .= '<li class="quizItem quizItemHover items-center">';
                    $html .= '  <input class="mr-2.5" type="checkbox" id="answer-'.$answer->getId().'" name="answer-'.$answer->getId().'">';
                    $html .= ' <div class="managementContent">'.$answer->getAnswerString().'</div>';
                    $html .= '</li>';
                    $html .= '</label>';
                }
                $html .= '</ul>';
                $html .= $formClose;
                break;

            case 11: // Text long answers
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">';
                $html .= ngettext("Answer the question below.", "Answer the questions below", sizeof($this->playerQuestAnswers));
                $html .='</div>';
                $html .= '<ul class="mt-2">';
                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<label class="group" for="answer-'.$answer->getId().'">';
                    $html .= '<li class="quizItem quizItemHover block">';
                    if (strlen($answer->getQuestion())) {
                        $html .= '<div class="managementContent">'.$answer->getQuestion().'</div>';
                    }
                    $html .= '<textarea id="answer-'.$answer->getId().'" name="answer-'.$answer->getId().'" class="w-full mt-3"></textarea>';
                    $html .= '</li></label>';
                }
                $html .= '</ul>';
                $html .= $formClose;
                break;

            case 12: // Calculation
                $calculatedAnswer = "";

                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">';
                $html .= ngettext("Calculate the correct value below", "Calculate the correct values below", sizeof($this->playerQuestAnswers));
                $html .='</div>';
                $html .= '<ul class="mt-2">';
                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<label class="group" for="answer-'.$answer->getId().'">';
                    $html .= '<li class="quizItem quizItemHover block">';
                    if (strlen($answer->getQuestion())) {
                        $html .= '<div class="managementContent">'.$answer->getQuestion().'</div>';
                    }
                    /* Prefixed input like in Management */
                    $html .= '<span class="inline-flex rounded-md shadow-sm w-full mt-3">';
                    $html .= '<span class="inline-flex items-center px-2 rounded-l-md border border-r-0 border-black dark:border-gray-300 bg-gray-300 dark:bg-gray-800 text-gray-500 dark:text-gray-400">';
                    $html .= '\('.$answer->getName().'=\)';
                    $html .= '</span>';
                    $html .= '<input class="rounded-none rounded-r-md border-l-0 w-full" type="text" id="answer-'.$answer->getId().'" name="answer-'.$answer->getId().'" value="'.$calculatedAnswer.'" />';
                    $html .= '</span>';
                    $html .= '</li></label>';
                }
                $html .= '</ul>';
                $html .= $formClose;
                break;

            case 13: // Coordinates
                // Check that the player is at the correct coordinates.
                $player = new Player($this->playerId, true);
                if ($player->getMapID() == $this->resultMapId && $player->getX() == $this->resultX && $player->getY() == $this->resultY) {
                    $html = $this->questSuccess();
                } else {
                    $html .= '<div class="my-2">'._("Can you find the location?").'<br>'._("If it doesn't seem to be anywhere on the map, you can drop the quest and I may give you another place to look.").'</div>';
                }
                break;

            case 14: // Speech Recognition
                $html .= '<div class="managementContent">'.$this->getContent().'</div>';
                $html .= $formOpen;
                $html .= '<div class="text-sm opacity-70">';
                $html .= _("Click the microphone button and").' '.ngettext("speak the answer","speak the answers", sizeof($this->playerQuestAnswers));
                $html .= '</div>';
                $html .= '<div id="speechError" class="error"></div>';
                $html .= '<ul class="mt-2">';
                foreach($this->playerQuestAnswers as $answer) {
                    $html .= '<li class="quizItem quizItemHover block">';
                    if (strlen($answer->getQuestion())) {
                        $html .= '<div class="managementContent">'.$answer->getQuestion().'</div>';
                    }
                    $html .= '<textarea readonly id="answer-'.$answer->getId().'" name="answer-'.$answer->getId().'" class="w-full mt-3"></textarea>';
                    // Microphone button
                    $html .= '<button type="button" style="display: block; margin-right: auto; margin-left: auto;" onclick="startListening(event, \'answer-'.$answer->getId().'\', this);"><img alt="Microphone" src="images/speech.png" width="48" height="48" id="listenbtn"></button>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
                $html .= $formClose;
                break;

            case 16:
                $html .= '<div class="managementContent">'.$this->getContent().'<div class="text-center">';
                foreach($this->playerQuestAnswers as $answer) {
                    $salt = "Kuan-Hsing Wu & Pei-Shan Yu";
                    $crc = md5($this->id . $salt . $this->playerId . $this->getContent());
                    $url = $answer->getQuestion().'&quest_id='.$this->id.'&player_id='.$this->playerId.
                         '&port=https&crc='.$crc;
                    $html .= '<button class="btn highlight" onclick="startConversation('.$this->id.','.
                          $this->playerId.',\''.$url.'\')">Go to quest</a>';
                }
                $html .= '</div></div>';
                break;
                
            default: // Not supported
                $html .= _("This quest type").' ('.$this->resultTypeId.' - '.$this->resultType.") "._("is not currently supported.");
        }// end switch

        $html .= '</div>'; // Close questContent
        return $html;
    }

    /**
     * getContentViewItemQuiz renders the HTML for quest quizzes that use "items" as the questions.
     * There are a number of variants.
     * - True/false (pair of radios)
     * - Choose one (radio button)
     * - Choose multiple (checkbox)
     * - Order (drag handle)
     * - Calculation (numeric inputs, uses player variables)
     * - Short Answer (text inputs, uses ws-nlp.vipresearch.ca)
     *
     * @return string
     */
    protected function getContentViewItemQuiz(): string
    {
        $itemIds = array();
        // Styles used by most items
        $html = "";

        // List all the quest items
        $html .= '<ul class="mt-2'.($this->resultTypeId==5?' sortable" id="sortableQuizList':'').'">';

        foreach($this->questItems as $questItem) {
            array_push($itemIds, $questItem->getId());
            switch ($this->resultTypeId) {
                case 2: // T/F
                    $html .= '<li class="quizItem items-start flex-col-reverse"><div class="flex m-auto mb-1 space-x-2">';
                    // These True/False spaces might be a pain for localization. For now I'll make them show the whole word but only in small layouts
                    $html .= '<label class="trueFalse" for="item-'.$questItem->getId().'-T">' .
                        '<input class="mr-1" type="radio" id="item-'.$questItem->getId().'-T" name="item-'.$questItem->getId().'" value="1">'._("True").
                        '</label>';
                    $html .= '<label class="trueFalse" for="item-'.$questItem->getId().'-F">' .
                        '<input class="mr-1" type="radio" id="item-'.$questItem->getId().'-F" name="item-'.$questItem->getId().'" value="0">'._("False").
                        '</label>';
                    $html .= '</div>';
                    break;
                case 3: // Radio
                    $html .= '<label class="group" for="item-'.$questItem->getId().'">';
                    $html .= '<li class="quizItem quizItemHover items-center">';
                    $html .= '<input class="mr-2.5" type="radio" id="item-'.$questItem->getId().'" name="item" value="'.$questItem->getId().'">';
                    break;
                case 4: // Checkbox
                    $html .= '<label class="group" for="item-'.$questItem->getId().'">';
                    $html .= '<li class="quizItem quizItemHover items-center">';
                    $html .= '<input class="mr-2.5" type="checkbox" id="item-'.$questItem->getId().'" name="item-'.$questItem->getId().'">';
                    break;
                case 5: // Drag & Drop
                    $html .= '<li class="quizItem items-center active:shadow-inner-dark active:bg-gray-200 dark:active:bg-gray-900/50" data-id="'.$questItem->getId().'">';
                    $html .= '<i class="grabber fas fa-align-justify mr-3 text-xl opacity-80 text-gray-200 dark:text-gray-800"></i>';
                    break;
                case 6: // Text
                case 15: // Calculation
                    $html .= '<label class="group" for="item-'.$questItem->getId().'">';
                    $html .= '<li class="quizItem quizItemHover items-start flex-col-reverse">';
                    $html .= '<input class="w-full mt-2 mb-1" type="text" id="item-'.$questItem->getId().'" name="item-'.$questItem->getId().'" placeholder="'._("Your answer").'">';
            } // end switch
            // Display the image icon and item name
            $html .= '<div class="flex flex-wrap items-center text-sm">';
            $html .= ' <div class="w-8 h-8 mr-2 overflow-hidden items-end bg-contain bg-center bg-no-repeat" style="background-image:url('.$questItem->getImageFilename().');"></div>';
            $html .= ' <div class="font-bold mb-1">'.$questItem->getName().'</div>';
            // Display the description below
            $html .= ' <div class="w-full">'.$questItem->getDescription().'</div>';
            $html .= '</div></li>';

            // Close the label for types other than 2 & 5 that make the entire li a label
            if ( !in_array($this->resultTypeId, [2,5]) ) {
                $html .= '</label>';
            }
        } // end foreach
        $html .= '<input type="hidden" name="itemSortOrder" id="itemSortOrder" value="'.implode(',', $itemIds).'" />';
        $html .= '</ul>';
        return $html;

    } // end getContentViewItemQuiz()


    /**
     * Returns true if passed the correct location map and coordinates for a quest.
     * Can determine player's location if the parameters are null, but that may worsen performance.
     * @param int|null $mapId - Player's current map
     * @param int|null $x - Player's current location X
     * @param int|null $y - Player's current location Y
     * @return bool - true if the player is at the correct location
     * @throws Exception - throws Exception if there is no MapId, X or Y on a coordinates quest type
     */
    public function isCorrectLocation(int $mapId=null, int $x=null, int $y=null):bool
    {
        if ($this->getResultTypeId() != 13) return false;

        if (is_null($mapId) || is_null($x) || is_null($y)) {
            $player = new Player($this->playerId, true);
            $mapId = $player->getMapId();
            $x = $player->getX();
            $y = $player->getY();
        }

        // Throw error if the quest isn't set up correctly.
        if ( !is_int($this->getResultMapId())
            || !strlen($this->getResultX())
            || !strlen($this->getResultY()) )
        {
            throw new Exception('<div class="error">Coordinates quest id ' . $this->getId() . ' is not correctly configured. Ensure it has a valid Map ID and X and Y equations.</div>');
        } else if ($mapId == $this->resultMapId) {
            $m = new EvalMath;
            $m->suppress_errors = true;
            // Add the player's variables into the object so they will be used in calculating the correct answer
            foreach ($this->getVariables() as $name => $val) {
                $m->evaluate("$name=$val");
            }
            $calculatedX = $m->evaluate($this->resultX);
            $calculatedY = $m->evaluate($this->resultY);

            if ($x == $calculatedX && $y == $calculatedY) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an array of item IDs sorted by the answer_seq
     * @return array
     */
    public function getQuestItemIdsByAnswerSeq(): array
    {
        $sorted = [];
        foreach ($this->questItems as $questItem) {
            $sorted[(int)$questItem->getAnswerSeq()] = $questItem->getId();
        }
        ksort($sorted);

        // Now output only the IDs, in order
        $result = [];

        foreach ($sorted as $val) {
            array_push($result, $val);
        }
        return $result;
    }

    /**
     * Accepts the post data from a quest form and determines if the responses were correct for this quest.
     * If successful, handles quest completion. Returns the results (including applicable rewards) as HTML.
     * @param $post
     * @return string
     */
    public function checkAnswers($post):string
    {
        $html = "";
        // Keep a score which we ultimately evaluate as pass or fail.
        // This is so we can use automarking and evaluate against
        // the base_reward_automark_percentage
        $score = 0;
        // A count of the number of points possible
        $maxScore = 1;
        switch ($this->resultTypeId) {
            case 2: // Items True/False
                $maxScore = 0;
                foreach($this->questItems as $questItem) {
                    $maxScore++;
                    if ( isset($post['item-'.$questItem->getId()]) ) {
                        // Ensure the 1/0 values are proper booleans, just like the getAnswerBool values are
                        $value = (int)$post['item-'.$questItem->getId()] == 1;
                        if ($value == $questItem->getAnswerBool()) {$score++;}
                    }
                }
                break;

            case 3: // Items Choose One
                // Note that the way this works, selecting any true item will give the player full points (1/1)
                foreach($this->questItems as $questItem) {
                    if ($questItem->getAnswerBool() && isset($post['item']) && (int)$post['item'] == $questItem->getId() ) {
                        $score = 1;
                        break;
                    }
                }
                break;

            case 4: // Items Choose Multiple
                foreach($this->questItems as $questItem) {
                    // If the checkbox was checked and the value OR checkbox unchecked (not present) and value is false
                    $score = (int)($questItem->getAnswerBool() == isset($post['item-' . $questItem->getId()]));
                    // If the player got one wrong there's no point in continuing
                    if ($score == 0) break;
                }
                break;

            case 5: // Items Order
                $correctItemOrder = implode(',', $this->getQuestItemIdsByAnswerSeq());
                if ($correctItemOrder == $post['itemSortOrder']) {
                    $score = 1;
                }
                break;

            case 6: // Items Text Answers
                $maxScore = 0;
                foreach($this->questItems as $questItem) {
                    $maxScore++;
                    if ( isset($post['item-'.$questItem->getId()]) ) {
                        $playerAnswer = $post['item-'.$questItem->getId()];
                        // Use simple string comparison
                        // I debated removing punctuation but that may be critical for programming-related questions.
                        $mark = Helpers::similarity($questItem->getAnswerString(), $playerAnswer, false, true);
                        if ($mark >= 0.85) {$score++;}
                    }
                }
                break;

            case 15: // Items Calculation Answers
                $maxScore = 0;
                foreach($this->questItems as $questItem) {
                    $maxScore++;
                    if ( isset($post['item-'.$questItem->getId()]) ) {
                        // Calculate the correct answer
                        $m = new EvalMath;
                        $m->suppress_errors = true;
                        // Add the player's variables into the object so they will be used in calculating the correct answer
                        foreach($this->variables as $name=>$val) {
                            $m->evaluate("$name=$val");
                        }
                        $calculatedAnswer = $m->evaluate($questItem->getAnswerString());

                        // Trim spaces from player's answer
                        $playerAnswer = trim($post['item-'.$questItem->getId()]);
                        // Round player's answer to two decimal points (or more, if configured that way in config).
                        $playerAnswer = round($playerAnswer, Config::PRECISION_DIGITS);
                        $calculatedAnswer = round($calculatedAnswer, Config::PRECISION_DIGITS);

                        if ($playerAnswer == $calculatedAnswer) {$score++;}
                    }
                }
                break;

            case 7: // Cloze (Fill in Blanks)
                $maxScore = 0;
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    $maxScore++;
                    if (isset($post['answer-'.$playerQuestAnswer->getId()])) {
                        $playerAnswer = $post['answer-'.$playerQuestAnswer->getId()];
                        $mark = Helpers::similarity($playerQuestAnswer->getAnswerString(), $playerAnswer, false, true);
                        if ($mark >= 0.85) {$score++;}
                    }
                }
                break;

            case 8: // True/False
                $maxScore = 0;
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    $maxScore++;
                    if ( isset($post['answer-'.$playerQuestAnswer->getId()]) ) {
                        // Ensure the 1/0 values are proper booleans, just like the getAnswerBool values are
                        $value = (int)$post['answer-'.$playerQuestAnswer->getId()] == 1;
                        if ($value == $playerQuestAnswer->getCorrectBool()) {$score++;}
                    }
                }
                break;

            case 9: // Choose One
                // Note that the way this works, selecting any true item will give the player full points (1/1)
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    if ($playerQuestAnswer->getCorrectBool() && isset($post['answer']) && (int)$post['answer'] == $playerQuestAnswer->getId() ) {
                        $score = 1;
                        break;
                    }
                }
                break;

            case 10: // Choose Multiple
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    // If the checkbox was checked and the value OR checkbox unchecked (not present) and value is false
                    $score = (int)($playerQuestAnswer->getCorrectBool() == isset($post['answer-' . $playerQuestAnswer->getId()]));
                    // If the player got one wrong there's no point in continuing
                    if ($score == 0) break;
                }
                break;

            case 11: // Long answers - submitted to automarking API.
            case 14: // Speech to text.
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    if (isset($post['answer-'.$playerQuestAnswer->getId()])) {
                        $playerAnswer = $post['answer-'.$playerQuestAnswer->getId()];
                        $this->addToAutomarks($playerQuestAnswer->getId(), $this->playerId, $playerAnswer);
                    }
                }
                $out = shell_exec('php automark.php '.$this->getId().' '.$this->playerId.' > /dev/null 2>/dev/null &');
                return $this->questWaiting();

            case 12: // Calculation
                $maxScore = 0;
                foreach($this->playerQuestAnswers as $playerQuestAnswer) {
                    $maxScore++;
                    if ( isset($post['answer-'.$playerQuestAnswer->getId()]) ) {

                        $m = new EvalMath;
                        $m->suppress_errors = true;
                        // Add the player's variables into the object so they will be used in calculating the correct answer
                        foreach($this->variables as $name=>$val) {
                            $m->evaluate("$name=$val");
                        }
                        $calculatedAnswer = $m->evaluate($playerQuestAnswer->getAnswerString());

                        // Trim spaces from player's answer
                        $playerAnswer = trim($post['answer-'.$playerQuestAnswer->getId()]);
                        // Round player's answer to two decimal points (or more, if configured that way in config).
                        $playerAnswer = round($playerAnswer, Config::PRECISION_DIGITS);
                        $calculatedAnswer = round($calculatedAnswer, Config::PRECISION_DIGITS);

                        if ($playerAnswer == $calculatedAnswer) {$score++;}
                    }
                }
                break;

            case 13: // Coordinates. Not sure we will use this case, but it's here if we need it.
                if ($this->isCorrectLocation()) {
                    $score = 1;
                }

            default:
                $html .= _("Checking answers is not supported for this quest type.");
        }

        // Debugging
        // $html .= "Your score: ".$score." of ".$maxScore;

        if ($score >= $maxScore) {
            // Handle successful completion of quest
            $html .= $this->questSuccess();
        } else {
            // Mark quest as having been failed
            $html .= $this->questFail();
        }

        return $html;

    } // end checkAnswers()

    
    
} // end PlayerQuest
