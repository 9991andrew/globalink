<?php

/**
* Items that an NPC has for sale or gives for quests.
* Extends Item, adding quest_id npc_id to the Item class.
*/
class NpcItem extends Item {

    protected $npcId;
    protected $questId;

     /**
     * NpcItem constructor, needs array arg with all properties
     * @param arg - either id or an array containing all item values from an SQL query
     * @throws Exception
     */

    public function __construct($arg)
    {

        if(is_array($arg)) {
            // Create the base item
            parent::__construct($arg);

            // Add any properties specific to playerItem
            $this->npcId = (int)$arg['npc_id'];
            $this->questId = isset($arg['quest_id'])?(int)$arg['quest_id']:null;

        } else { // arg is probably invalid, we don't support another way to create an NPCItem
            throw new InvalidItemException();
        }
    }

    public function getNpcId() {
        return $this->npcId;
    }

    public function getQuestId() {
        return $this->questId;
    }

    /**
    * getClientNpcItemData: Returns essential tile data for client-side interaction
    */
    public function getClientNpcItemData() {
        $npcItemData = array();
        $npcItemData["questId"] = (int)$this->questId;
        $npcItemData["name"] = $this->name;
        $npcItemData["img"] = $this->imageFilename;
        $npcItemData["price"] = (int)$this->price;
        $npcItemData["description"] = $this->description;
        return $npcItemData;
    }    

}