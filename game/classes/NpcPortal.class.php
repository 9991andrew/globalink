<?php

/**
* A portal an NPC may have that can move a player to another location for a given price.
*/
class NpcPortal extends Dbh {
    protected $id;
    protected $npcId;
    protected $name;
    protected $destMapId;
    protected $destX;
    protected $destY;
    protected $level;
    protected $price;

    /**
     * NpcPortal constructor. Needs a npc_portal_id or an associative array from a query of npc_portals.
     * @param arg either array or id
     * @throws Exception
     */
    public function __construct($arg)
    {
        if(is_int($arg)) {

            $npcPortalId = $arg;

            $sql = "SELECT * FROM npc_portals WHERE id = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$npcPortalId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new InvalidNPCPortalException($arg);
            
            // Now assign row to arg and we can use the same code as if
            // we passed the row to the constructor
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidNPCPortalException();
        }

        $this->id=(int)$arg['id'];
        $this->npcId = $arg['npc_id'];
        $this->name = $arg['name'];
        $this->destMapId = is_null($arg['dest_map_id'])?null:(int)$arg['dest_map_id'];
        $this->destX = is_null($arg['dest_x'])?null:(int)$arg['dest_x'];
        $this->destY = is_null($arg['dest_y'])?null:(int)$arg['dest_y'];
        $this->level = (int)$arg['level'];
        $this->price = (int)$arg['price'];
    }

    // Getters for all data
    public function getId() {return $this->id;}
    public function getNpcId() {return $this->npcId;}
    // Could make a getter for the actual NPC object, but I don't see that being needed.
    public function getName() {return $this->name;}
    public function getLevel() {return $this->level;}
    public function getPrice() {return $this->price;}
    public function getDestMapId() {return $this->destMapId;}
    public function getDestX() {return $this->destX;}
    public function getDestY() {return $this->destY;}


    /**
    * getClientBuildingData: Returns essential tile data for client-side interaction
    */
    public function getClientNPCPortalData() {
        $portalData = array();
        $portalData["npid"] = $this->id;
        $portalData["name"] = $this->name;
        $portalData["level"] = $this->level;
        $portalData["price"] = $this->price;
        $portalData["dMapId"] = $this->destMapId;
        $portalData["dX"] = $this->destX;
        $portalData["dY"] = $this->destY;
        return $portalData;
    }

}
