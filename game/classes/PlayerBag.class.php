<?php

/**
 * A bag owned by a player. All players have a primary bag. This class provides functions to display what is in a bag.
 */
class PlayerBag extends Bag {
    // Array of all the bag slots with the playerItem each contains (if any)
    protected $slotItems;


    /**
     *  Main constructor
     * @param int $playerId - Id of the player
     * @param int $bagId - Id of the bag from the bags table (defaults to 1)
     */
    public function __construct(int $playerId, int $bagId=1)
    {
        $this->playerId = $playerId;
        parent::__construct($bagId);

        // Look up player's items.
        // I want to also include blank slots in this view...
        $sql = "SELECT pi.id AS player_item_id, pi.item_id, pi.quest_id, pi.player_id, bs.id AS bag_slot_id, bs.bag_id, bs.slot_seq, pi.guid, i.id, pi.is_equipped,
        pi.bag_slot_amount, i.name, i.item_category_id, ic.name AS item_category_name,
        i.item_icon_id, i.description, i.item_effect_id, i.effect_parameters, i.weight, i.parameters, i.required_level,
        i.level, i.hand, i.price, i.amount, i.max_amount, ico.name AS icon_name
        FROM bag_slots bs
            LEFT JOIN player_items pi ON pi.bag_slot_id=bs.id AND player_id = ?
            LEFT JOIN items i ON i.id = pi.item_id
            LEFT JOIN item_categories ic ON i.item_category_id = ic.id
            LEFT JOIN item_icons ico ON i.item_icon_id=ico.id
            WHERE bag_id= ? ORDER BY slot_seq;";


        // I feel like this could be pretty redundant, but it has its use.
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$playerId, $bagId]);
        $this->slotItems = array();
        while ($row = $stmt->fetch()) {
            if (isset($row['guid']) && strlen($row['guid']))
                array_push($this->slotItems, array("slotId"=>(int)$row['bag_slot_id'], "playerItem"=>new PlayerItem($row)));
            else 
                array_push($this->slotItems, array("slotId"=>(int)$row['bag_slot_id'], "playerItem"=>null));
        }            

    } // end constructor

    public function getSlotItems() {return $this->slotItems;}

    /**
     * Gets a compact array of a player's bag slots and details of items within
     */
    public function getClientSlotItemsData() {
        $slots = array();
        foreach ($this->slotItems as $slotItem) {
            $slot = array();
            $slot['id'] = $slotItem['slotId'];
            if (is_null($slotItem['playerItem'])) $slot['pitm'] = null;
            else {
                $pi = $slotItem['playerItem'];
                $slot['pitm'] = array();
                $slot['pitm']['itemGUID'] = $pi->getGUID();
                $slot['pitm']['id'] = $pi->getId();
                $slot['pitm']['qty'] = $pi->getSlotAmount();
                $slot['pitm']['description'] = $pi->getDescription();
                $slot['pitm']['name'] = $pi->getName();
                $slot['pitm']['level'] = $pi->getLevel();
                $slot['pitm']['reqLevel'] = $pi->getRequiredLevel();
                $slot['pitm']['img'] = $pi->getImageFilename();
            }
            array_push($slots, $slot);
        }
        return $slots;
    }

    /**
     * Shows the number of available slots in a player's bag.
     * @return int Number of available slots in this player's bag
     */
    public function getAvailableSlots() {
        $emptyCount = 0;
        foreach ($this->slotItems as $item) {
            if (is_null($item['playerItem'])) $emptyCount++;
        }
        return $emptyCount;
    }

    /**
     * getItemsWithId - returns an array of playerItems with a specific item_id
     * @param mixed $arg - id or playerItem object
     * @param bool $ignoreMaxedStacks - won't list stacks that are already full (since there's no point in stacking them)
     * @param string $excludeItemGUID -- specific playerItem instance to exclude
     * @return array of items with the specified type
     */
    public function getItemsWithSameId($arg, bool $ignoreMaxedStacks=true, string $excludeItemGUID=null): array {
        if (is_object($arg)) {
            $itemId = $arg->getId();
            $excludeItemGUID = $arg->getGUID();
        }
        else $itemId=$arg;
        $playerItems = array();
        foreach($this->slotItems as $slot) {
            $playerItem = $slot['playerItem'];
            if (is_null($playerItem)) continue;
            if ($ignoreMaxedStacks) {
                if ($playerItem->getSlotAmount() >= $playerItem->getMaxAmount()) continue;
            }
            if ($playerItem->getId() == $itemId && $playerItem->getGUID() != $excludeItemGUID) array_push($playerItems, $playerItem);
        }
        return $playerItems;
    }

    /**
     * Returns the item in the slot with a certain Id
     * @param int $slotId
     * @return PlayerItem
     * @throws Exception
     * @throws EmptyBagSlotException
     */
    public function getItemInSlot(int $slotId):PlayerItem {
        // This works as long as player has bag 1, but this should be determined
        // by searching the bag for a matching slot id. If more bags are introduced,
        // this method of getting the slot will have to be revised
        $slotIdx = $slotId - 1;
        if ($slotId < 1 || $slotId > sizeof($this->slotItems))
            throw new Exception('Invalid slot Id.');
        $playerItem = $this->slotItems[$slotIdx]['playerItem'];
        if (is_null($playerItem)) throw new EmptyBagSlotException($slotId);
        return $playerItem;
    }


    /**
     * swapBagSlots switches the item inventory slots for a player.
     * This should be a playerBag method
     * @param int $srcSlot
     * @param int $destSlot
     */
    public function swapBagSlots(int $srcSlot, int $destSlot)
    {
        try {
            $srcItem = $this->getItemInSlot($srcSlot);
        } catch (EmptyBagSlotException $e) {
            $srcItem = null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        try {
            $destItem = $this->getItemInSlot($destSlot);
        } catch (EmptyBagSlotException $e) {
            $destItem = null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (is_null($srcItem) && is_null($destItem)) throw new Exception('At least one slot must have an item in it to swap items.' );

        if (is_null($srcItem)) {
            $playerItem = $destItem;
            $destSlot = $srcSlot;
        }

        if (is_null($destItem)) {
            $playerItem = $srcItem;
        }

        // Case where only one item needs to be moved
        if (isset($playerItem)) {
            // Do SQL query to switch item slot
            $playerItemId = $playerItem->getPlayerItemId();
            $sql = "UPDATE player_items SET bag_slot_id = ? WHERE id = ? AND player_id = ?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$destSlot, $playerItemId, $this->playerId]);
            $playerItem->setSlotId($destSlot);

            // Update this bag slot
            $this->slotItems[$srcSlot-1]['playerItem'] = null;
            $this->slotItems[$destSlot-1]['playerItem'] = $playerItem;

        } else {
            // Do SQL query to swap two items' slots. Unfortuantely MySQL doesn't allow this to be done
            // In one relatively simple query using JOINs and such, so this takes three queries
            // As you can't violate the foreign key constraint but you CAN have NULL values temporarily.
            $srcPlayerItemId = $srcItem->getPlayerItemId();
            $destPlayerItemId = $destItem->getPlayerItemId();
            // Start by nulling the destination field so the source can be moved there,
            // then set the destination item to the source slot
            $sql = "UPDATE player_items SET bag_slot_id = NULL WHERE id = ? AND player_id = ?;
                    UPDATE player_items SET bag_slot_id = ? WHERE id = ? AND player_id = ?;
                    UPDATE player_items SET bag_slot_id = ? WHERE id = ? AND player_id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$destPlayerItemId, $this->playerId,
                $destSlot, $srcPlayerItemId, $this->playerId,
                $srcSlot, $destPlayerItemId, $this->playerId]);

            $srcItem->setSlotId($destSlot);
            $destItem->setSlotId($srcSlot);
            // Update the bag slots
            $this->slotItems[$srcSlot-1]['playerItem'] = $destItem;
            $this->slotItems[$destSlot-1]['playerItem'] = $srcItem;

        }
    } // end swapBagSlots()


}