<?php

/**
* A building that may exist on a given map tile.
*/
class Building extends Dbh {
    protected $id;
    protected $name;
    protected $level;
    protected $mapId;
    protected $x;
    protected $y;
    protected $destMapId;
    protected $destX;
    protected $destY;
    protected $externalLink;
    protected $allowedProfessionIds;

    /**
     * Building constructor. Needs a building_id, a map_id, x and y, or an associative array from a query of the building.
     * @param mixed $arg either array, building_id, or map_id
     * @param int $x - if querying with map_id, x coordinate
     * @param int $y - if querying with map_id, y coordinate
     * @throws Exception
     */
    public function __construct($arg, int $x=null, int $y=null)
    {
        if(is_int($arg) && is_int($x) && is_int($y)) {

            $mapId = $arg;
            // We add a building_ prefix for some fields so that field names line up with the fields in the query
            // from map.class.php, which includes building details for each tile.
            $sql = "SELECT id AS building_id, name AS building_name, level AS building_level, map_id, x, y, dest_map_id, dest_x, dest_y, external_link
                FROM buildings WHERE map_id = ? AND x = ? AND y = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$mapId, $x, $y]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidBuildingException($mapId, $x, $y);
            
            // Now assign row to arg and we can use the same code as if
            // we passed the row to the constructor
            $arg = $stmt->fetch();
        } else if (is_int($arg)) {
            $buildingId = $arg;
            $sql = "SELECT id AS building_id, name AS building_name, level AS building_level, map_id, x, y, dest_map_id, dest_x, dest_y, external_link FROM buildings WHERE id = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$buildingId]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidBuildingException($buildingId);
            $arg = $stmt->fetch();

        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidBuildingException();
        }

        $this->id=(int)$arg['building_id'];
        $this->name = $arg['building_name'];
        $this->level = (int)$arg['building_level'];
        $this->mapId = (int)$arg['map_id'];
        $this->x = (int)$arg['x'];
        $this->y = (int)$arg['y'];
        $this->destMapId = is_null($arg['dest_map_id'])?null:(int)$arg['dest_map_id'];
        $this->destX = is_null($arg['dest_x'])?null:(int)$arg['dest_x'];
        $this->destY = is_null($arg['dest_y'])?null:(int)$arg['dest_y'];
        $this->externalLink = $arg['external_link'];

        // Fetch the list of allowed professions for this building
        $sql = "SELECT profession_id FROM building_professions WHERE building_id = ?";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$this->id]);

        // This gets the returned column as array of ints
        $this->allowedProfessionIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    }

    // Getters for all data
    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    public function getLevel() {return $this->level;}
    public function getMapId() {return $this->mapId;}
    public function getX() {return $this->x;}
    public function getY() {return $this->y;}
    public function getDestMapId() {return $this->destMapId;}
    public function getDestX() {return $this->destX;}
    public function getDestY() {return $this->destY;}
    public function getExternalLink() {return $this->externalLink;}
    /**
     * @return array
     */
    public function getAllowedProfessionIds(): array
    {
        return $this->allowedProfessionIds;
    }

    /**
     * Returns true if the specified playerId is allowed to use the portal
     * because they have an allowed profession
     * @param $playerId
     * @return bool
     */
    public function playerAllowed($playerId): bool
    {
        // If no professions are required, allow the player
        if (sizeof($this->allowedProfessionIds)==0) return true;
        // Otherwise do a DB lookup to see if the player has any allowed professions.
        $sql = "SELECT 1 FROM player_professions WHERE player_id = ? AND profession_id IN (SELECT profession_id FROM building_professions WHERE building_id = ?) LIMIT 1;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$playerId, $this->id]);

        if ($stmt->fetchColumn()==1) return true;
        else return false;
    }

    /**
     * If this portal has a destination map, this function returns the name of that map
     * @return string|null
     */
    public function getDestMapName()
    {
        if (is_int($this->destMapId)) {
            $sql = "SELECT name from maps WHERE id = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->destMapId]);
            return $stmt->fetchColumn();
        }
        return null;
    }

    /**
    * getClientBuildingData: Returns essential tile data for client-side interaction
    */
    public function getClientBuildingData() {
        $buildingData = array();
        $buildingData["bid"] = $this->id;
        $buildingData["name"] = $this->name;
        $buildingData["level"] = $this->level;
        $buildingData["dMapId"] = $this->destMapId;
        $buildingData["dX"] = $this->destX;
        $buildingData["dY"] = $this->destY;
        $buildingData["link"] = $this->externalLink;
        $buildingData["profs"] = $this->allowedProfessionIds;
        return $buildingData;
    }

}