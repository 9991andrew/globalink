<?php

/**
* A map in MEGA World that is comprised of many tiles and contains NPCs, Buildings, and Players.
*/
class Map extends Dbh {
    // id from the maps table
    protected $id;
    // Name of map area
    protected $name;
    protected $mapTypeId;
    // map_type_name - eg village, Gludio, city
    protected $typeName;
    // map_description
    protected $typeDescription;
    // Mostly blank but this could describe the purpose or nature of the map
    protected $description;
    // width of map
    protected $sizeX;
    // should always be 0
    protected $minX;
    protected $maxX;
    // depth of map
    protected $sizeY;
    // should always be 0
    protected $minY;
    protected $maxY;
    protected $tiles;

    // Array of NPC objects that live on this map
    protected $npcs;


    /**
     * Map constructor. Needs a map_id.
     * @param int $id - Id of the map from the maps table
     * @throws Exception
     */
    public function __construct(int $id)
    {
        $this->id=$id;
        // Look up information about given map.

        // Check that map exists and throw exception if not.
        $sql = "SELECT m.id, m.name, m.map_type_id, m.description, mt.name AS map_type_name, mt.description AS map_type_description FROM maps m JOIN map_types mt ON mt.id=m.map_type_id WHERE m.id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$id]);
        $count = $stmt->rowCount();
        if ($count == 0) throw new InvalidMapException($id);
        $result = $stmt->fetch();
        $this->name = $result['name'];
        $this->typeId = $result['map_type_id'];
        $this->typeName = $result['map_type_name'];
        $this->description = $result['description'];
        $this->typeDescription = $result['map_type_description'];

        // Get map size, min, and max for each axis.
        $sql = "SELECT COUNT(DISTINCT x) as size_x, COUNT(DISTINCT y) AS size_y,
        MIN(x) AS min_x, MIN(y) AS min_y, MAX(x) AS max_x, MAX(Y) AS max_y  FROM map_tiles WHERE map_id = ?";


        $stmt = $this->connect()->prepare($sql);
        $stmt-> execute([$id]);
        $count = $stmt->rowCount();

        // Does it make sense to load a map with no tiles/
        // I guess we might make the object to edit the map
        // if ($count == 0) if ($count == 0) throw new Exception("Map $id has no map tiles.");
        if ($count > 0) {
            $result = $stmt->fetch();
            $this->sizeX = $result['size_x'];
            $this->minX = $result['min_x'];
            $this->maxX = $result['max_x'];
            $this->sizeY = $result['size_y'];
            $this->minY = $result['min_y'];
            $this->maxY = $result['max_y'];

            // Make a 2D array of all the tiles
            $this->tiles = array();

            // This complex query is starting to look like it should be a view.
            // Make it so if I'm finished with it, as I use a similar query in two places.
            $sql = "SELECT * FROM map_tiles_buildings_npcs_view WHERE map_id = ? ORDER BY x,y";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);

            // Check here that the number of rows returned matches sizeX*sizeY
            // and throw an exception if not (ie if someone deleted a DB row)
            $count = $stmt->rowCount();
            if ($count != $this->sizeX*$this->sizeY) {
                throw new Exception("Unexpected number of map tiles for map_id $id. Ensure all map_tiles are correctly set.");
            }

            // Loop through results creating MapTile objects and add them into a 2D array
            // Maps *should* always have min(y) 0 and min(x) 0 so map[x][y] should
            // always translate to the coordinates as per the DB row.
            while ($row = $stmt->fetch()) {
                // If a tile is missing this could throw things off.
                // The check above should throw an exception if a MapTile is missing.
                $x = (int)$row['x'];
                $y = (int)$row['y'];
                if (!isset($this->tiles[$x])) array_push($this->tiles, array());
                array_push($this->tiles[$x], new MapTile($row));
            }

            // Get all NPCs that live on this map
            $sql = "SELECT n.id, n.name, map_id, y_top, x_right, y_bottom, x_left,
            level, n.npc_icon_id, i.name AS icon_name,
            (SELECT 1 FROM npc_items WHERE n.id=n.id LIMIT 1) AS has_items, -- about half
            (SELECT 1 FROM quests WHERE giver_npc_id=n.id LIMIT 1) AS gives_quests, -- about 2/3
            (SELECT 1 FROM quests WHERE target_npc_id=n.id OR (giver_npc_id=n.id AND target_npc_id IS NULL) LIMIT 1) AS target_of_quests, -- about 1/3
            (SELECT 1 FROM npc_professions WHERE npc_id=n.id LIMIT 1) AS has_professions, -- about 1/3
            (SELECT 1 FROM npc_portals WHERE npc_id=n.id LIMIT 1) AS has_portals -- about 1/20
            FROM npcs n
            JOIN npc_icons i ON n.npc_icon_id=i.id
            WHERE n.map_id = ? ;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);

            $this->npcs = array();
            while ($row = $stmt->fetch()) {
                array_push($this->npcs, new Npc($row));
            }

        }// end if $count > 0

    }// end __construct

    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    public function getTypeId() {return $this->typeId;}
    public function getTypeName() {return $this->typeName;}
    public function getTypeDescription() {return $this->typeDescription;}
    public function getDescription() {return $this->description;}
    public function getSizeX() {return $this->sizeX;}
    public function getMinX() {return $this->minX;}
    public function getMaxX() {return $this->maxX;}
    public function getSizeY() {return $this->sizeY;}
    public function getMinY() {return $this->minY;}
    public function getMaxY() {return $this->maxY;}
    // Returns array of all the map's tiles
    public function getTiles() {return $this->tiles;}
    public function getNpcs() {return $this->npcs;}

    /**
     * Returns array of tile data used on client-side
     * @return array
     */
    public function getClientTilesData():array {
        $tilesArray = array();
        foreach ($this->tiles as $x) {
            $tilesArrayX = array();
            foreach ($x as $tile) {
                array_push($tilesArrayX, $tile->getClientTileData());
            }
            array_push($tilesArray, $tilesArrayX);
        }
        return $tilesArray;
    }

    public function getClientMapNpcsData() {
        $npcArray = array();
        foreach ($this->npcs as $npc) {
            array_push($npcArray, $npc->getClientNpcData());
        }
        return $npcArray;        
    }

    /**
     *  getTile
     * @param $x - x coordinate for this map object
     * @param $y - y coordinate for this map object
     */
    public function getTile($x, $y) {
        return $this->tiles[$x][$y];
    }


}//end Map class
