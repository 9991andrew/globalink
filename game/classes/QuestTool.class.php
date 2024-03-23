<?php

/**
 * Items that can be used to acquire other items.
 * Extends PlayerItem as it needs the PlayerItemVariables to compute the QuestToolLocations.
 */
class QuestTool extends PlayerItem {
    protected $questToolId;
    protected $amount;
    protected $questId;
    protected $locations;

    /**
     * Quest Tool constructor. Needs item id and quest id
     * @param string $itemGUID - player's GUID for their item
     * @param int $questId
     * @throws Exception
     */
    public function __construct(string $itemGUID, int $questId)
    {
        if(strlen($itemGUID) && is_int($questId)) {

            // Check that quest tool exists and throw exception if not.
            // a single item
            $sql = "SELECT qt.id AS quest_tool_id, pi.id AS player_item_id, pi.guid, pi.item_id, qt.quest_id, qt.item_amount,
                pi.player_id, pi.is_equipped, pi.bag_slot_id, pi.bag_slot_amount,
                i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.amount, i.max_amount,
                i.item_icon_id, ico.name AS icon_name
                FROM player_items pi
                JOIN quest_tools qt ON pi.item_id=qt.item_id
                LEFT JOIN items i ON i.id=pi.item_id
                LEFT JOIN item_categories ic ON i.item_category_id=ic.id
                LEFT JOIN item_icons ico ON i.item_icon_id=ico.id
                WHERE guid = ? AND qt.quest_id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$itemGUID, $questId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new Exception("There is no quest tool with the questId $questId and id $itemId");
            $arg = $stmt->fetch();

        } else {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new Exception("Invalid call to QuestTool constructor. Requires itemId and questId.");
        }

        // Create the base item
        parent::__construct($arg);

        // Add any properties specific to playerItem
        $this->questToolId = (int)$arg['quest_tool_id'];
        $this->questId = (int)$arg['quest_id'];
        $this->amount = (int)$arg['item_amount'];

        // Create the array of quest tool locations
        $sql = "SELECT * FROM quest_tool_locations WHERE quest_tool_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->questToolId]);

        $this->locations = array();
        while ($row = $stmt->fetch()) {
            array_push($this->locations, new QuestToolLocation($row));
        }


    } // end constructor

    /**
     * @return int
     */
    public function getQuestToolId(): int
    {
        return $this->questToolId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getQuestId(): int
    {
        return $this->questId;
    }

    /**
     * @return array
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Checks all the quest tool locations, giving the player any found items.
     * If items are found a message will be displayed for each one.
     * If nothing is found, an empty string is returned
     *
     * If no location is specified it will use the player's current location by default
     * @param int|null $mapId
     * @param int|null $x
     * @param int|null $y
     */
    public function useAtLocation(int $mapId=null, int $x=null, int $y=null)
    {
        $html = "";
        $questCompletionHtml = "";
        $notEnoughRoom = 0;
        // Use the player's current location if none is specified. (This is a bit more expensive)
        if (is_null($mapId)||is_null($x)||@is_null($y)) {
            $player = new Player($this->ownerPlayerId, true);
            $mapId = $player->getMapId();
            $x = $player->getX();
            $y = $player->getY();
        }

        // Iterate through all the questToolLocations
        foreach ($this->locations as $location) {
            if ($location->getMapId() == $mapId) {
                $m = new EvalMath;
                $m->suppress_errors = true;
                // Add the player's variables into the object so they will be used in calculating the correct answer
                foreach ($this->getVariables() as $name => $val) {
                    $m->evaluate("$name=$val");
                }
                $calculatedX = $m->evaluate($location->getX());
                $calculatedY = $m->evaluate($location->getY());

                if ($x == $calculatedX && $y == $calculatedY) {
                    // add the item to player's inventory. I think I need a player object, simple is okay
                    if (!isset($player)) {
                        $player = new Player($this->ownerPlayerId, true);
                    }

                    // We must check that this player doesn't already have the specified amount of this item already.
                    // TODO: Should only give the player the difference between what they have and what is given, currently a player
                    // could get an infinite amount by dropping some and digging for the full award amount.
                    if (!$player->hasItem($location->getItemId(), $location->getItemAmount())) {

                        // Instantiate an item. Can pass it to addItem to avoid duplication of effort
                        // and use that object to create the details view we show the user.
                        $item = new Item($location->getItemId());
                        // This is very similar to what is done when a player receives an item for quest (extract to function?)
                        if (strlen($html) == 0) {
                            $html = '<i class="fas fa-shopping-bag" style="margin-right:4px;"></i> <strong>'._("You have found the following").'</strong>' .
                                '<ul class="mt-2 ml-2">';
                        }
                        try {
                            $player->addItem($item, $location->getItemAmount());
                            $html .= '<li><i class="fas fa-check-circle text-green-500 dark:text-green-400" style="margin-right:8px"></i>' . $item->getName();
                            if ($item->getAmount() * $location->getItemAmount() > 1) $html .= " <strong>(" . $item->getAmount() * $location->getItemAmount() . ")</strong>";
                            $html .= "</li>";
                            // If there's a success message, show it.
                            if (strlen($location->getSuccessMessage())) $html .= '<li class="managementContent m-2">' . $location->getSuccessMessage() . '</li>';
                            if ($location->getQuestComplete()) {
                                $playerQuest = new PlayerQuest($this->questId, $this->ownerPlayerId);
                                // If we want more flexibility we could run $playerQuest->attemptToCompleteQuest()
                                $questCompletionHtml .= $playerQuest->questSuccess();
                            }
                        } catch (Exception $e) {
                            $notEnoughRoom++;
                            $html .= '<li class="error"><i class="fas fa-times-circle mr-2 text-red-600 dark:text-red-500"></i>'.("You found something but have no room.").'<br>' . $item->getName() . '</li>';
                        }
                    }// end player does not already have the item
                } // end if x && y
            } // end if mapId
        } // end locations foreach
        // Close the ul if necessary
        if (strlen($html)) $html .= "</ul>";

        // If there were any exceptions, we assume this is because the bag was full and tell the player.
        if ($notEnoughRoom) {
            $html .= '<p class="error"><strong>'._("Your bag is full!").'</strong> '._("Try again after freeing up space.").'</p>';
        }

        if (strlen($questCompletionHtml)) {
            $html .= $questCompletionHtml;
        }

        return $html;
    }// end useAtLocation()

} // end QuestItem