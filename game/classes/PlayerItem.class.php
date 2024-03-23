<?php

/**
* A item owned by a Player. adjustSlotAmount() method allows changing the quantity of an items.
*/
class PlayerItem extends Item {
    protected $playerItemId;
    protected $GUID; // An obfuscated id. Used in scenarios where a user might be able to specify or see the id.
    protected $ownerPlayerId;
    protected $isEquipped; // generally this applies to avatar parts, could apply to weapons and armour
    protected $slotId; // Applies to items in a player's bag, not equipped items
    protected $slotAmount; // different from amount, this is the player's amount
    protected $questId; // quest the player received the item for
    protected $variables; // array of item variables

    /**
     * Item constructor. Needs a GUID.
     * @param $arg - Either the itemGUID or a query row with all data.
     * @throws Exception
     */
    public function __construct($arg)
    {
        // This first section runs if we are already passed an array of all item info,
        // then we don't have to do any queries.
        // This section is still invoked by the constructor if we have to
        // first look up the item
        if(is_string($arg)) {
            $this->GUID = $arg;
            // Look up information about given item.

            // Check that item exists and throw exception if not.
            // a single item
            $sql = "SELECT pi.id AS player_item_id, pi.guid, pi.item_id, pi.quest_id, pi.player_id, pi.is_equipped, pi.bag_slot_id, pi.bag_slot_amount,
                i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.amount, i.max_amount,
                i.item_icon_id, ico.name AS icon_name
                FROM player_items pi
                LEFT JOIN items i ON i.id=pi.item_id
                LEFT JOIN item_categories ic ON i.item_category_id=ic.id
                LEFT JOIN item_icons ico ON i.item_icon_id=ico.id
                -- LEFT JOIN icon_extensions itemext ON i.icon_extension_id=itemext.id
                WHERE guid = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$arg]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidPlayerItemException($arg);
            $arg = $stmt->fetch();

        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidPlayerItemException();
        }

        // Create the base item
        parent::__construct($arg);

        // Add any properties specific to playerItem
        $this->playerItemId = (int)$arg['player_item_id'];
        $this->GUID = $arg['guid'];
        $this->ownerPlayerId = (int)$arg['player_id'];
        if ($arg['is_equipped'] == 1)
            $this->isEquipped = true;
        else $this->isEquipped = false;
        if (is_integer((int) $arg['bag_slot_id']))
            $this->slotId = (int) $arg['bag_slot_id'];
        $this->slotAmount = (int) $arg['bag_slot_amount'];
        $this->questId = is_null($arg['quest_id'])?null:(int) $arg['quest_id'];


        // Create any variables that are used for this quest
        $this->variables = array();
        $sql = "SELECT * FROM player_item_variables WHERE player_id = ? AND player_item_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->ownerPlayerId, $this->playerItemId]);
        while ($row = $stmt->fetch()) {
            $this->variables[$row['var_name']] = $row['var_value'];
        }

        if (sizeof($this->variables)) {
            $this->description = Helpers::injectVariables($this->description, $this->variables);
        }


    } // end constructor


    /**
    * getClientPlayerItemData: Returns essential tile data for client-side interaction
    */
    public function getClientPlayerItemData() {
        $playerItemData = array();
        $playerItemData["guid"] = $this->GUID;
        $playerItemData["name"] = $this->name;
        $playerItemData["img"] = $this->imageFilename;
        $playerItemData["qty"] = (int)$this->slotAmount;
        $playerItemData["slot"] = (int)$this->slotId;
        return $playerItemData;
    }

    /**
     * @return int
     */
    public function getPlayerItemId()
    {
        return $this->playerItemId;
    }

    /**
     * @return mixed
     */
    public function getGUID()
    {
        return $this->GUID;
    }

    /**
     * @return int
     */
    public function getOwnerPlayerId()
    {
        return $this->ownerPlayerId;
    }

    /**
     * @return bool
     */
    public function isEquipped(): bool
    {
        return $this->isEquipped;
    }

    /**
     * @return int
     */
    public function getSlotId(): int
    {
        return $this->slotId;
    }

    /**
     * Changes the slot id of an item.
     * NOTE: This does NOT update the DB, which has contraints that must be respected.
     * This method should only be accessed from the Player->swapBagSlots() method
     * to ensure the bag object is updated
     * @param int $slotId
     */
    public function setSlotId(int $slotId)
    {
        $this->slotId = $slotId;
    }

    /**
     * @return int
     */
    public function getSlotAmount(): int
    {
        return $this->slotAmount;
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
    public function getVariables(): array
    {
        return $this->variables;
    }

    /**
     * drop() removes an item from the player's inventory
     * TODO: Could check that the player doesn't have quests depending on this before dropping and warn them.
     */
    public function drop() {
        $sql = "DELETE FROM player_items WHERE guid = ? AND player_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->GUID, $this->ownerPlayerId]);

        // Not necessary - caller can handle this if necessary
        // $player->refreshPlayerItems();
    }

    /**
     * adjustSlotAmount() adds or subtracts the slotAmount of an item in a bag slot
     * @param int $increment The amount by which to adjust the slotAmount
     * @throws Exception
     */
    public function adjustSlotAmount(int $increment) {
        // Using all of an item is effectively the same as deleting it
        if (-1*$increment == $this->getSlotAmount()) return $this->drop();
        if (-1*$increment >= $this->getSlotAmount()) throw new Exception(_("You can't remove more of an item than you have."));
        if ($this->getSlotAmount()+$increment > $this->getMaxAmount()) throw new Exception(sprintf(_("Can't fit this many items in a slot. Maximum of this item is %d."), $this->getMaxAmount()));
        $this->slotAmount = $this->slotAmount+$increment;
        $sql = "UPDATE player_items SET bag_slot_amount = ? WHERE guid = ? AND player_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->slotAmount, $this->GUID, $this->ownerPlayerId]);
    }

} // end PlayerItem
