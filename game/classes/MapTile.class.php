<?php

/**
* An individual map tile that comprises part of a map.
*/
class MapTile extends Dbh {

    protected $mapId;
    protected $x;
    protected $y;
    protected $typeId;
    protected $typeName;
    protected $movementReq;
    protected $skillIdReq;
    protected $mapTileTypeIdImage;
    // Could make properties for icon_author and icon_extension_id, icon_ext, don't think I need those here, though

    // Array of NPC ids who may be on this tile
    protected $npcIds;

    const TILE_PATH = Config::IMAGE_PATH.'tile/';

    /**
    *
    */
    public static function getTilePath() {
        return MapTile::TILE_PATH;
    }

    /**
    * Most tiles need a default background behind the specified image because there are "holes".
    * This function returns that filename
    */
    public static function getDefaultTileFilename() {
        return MapTile::TILE_PATH.'invisible.png';
    }

    /**
    * Gets a list of the top $num map tiles. This is used primarily for preloading the common map tiles to make map loads look faster.
    * I'd use this with WebP images - the only browser that doesn't support this (without a developer setting turned on)
    * is also the only browser that doesn't support webP.
    * Safari users are jut going to have to wait for slow image loading, unfortunately.
    */
    public static function getTopTileIds(int $num=11) {
        $dbh = new Dbh();
        $sql = "SELECT COUNT(*) AS ct, mt.map_tile_type_id, mtt.name AS map_tile_type_name, mtt.map_tile_type_id_image FROM map_tiles mt
        JOIN map_tile_types mtt ON mtt.id=mt.map_tile_type_id
        GROUP BY mt.map_tile_type_id
        ORDER BY COUNT(*) DESC
        LIMIT :num ;";

        $stmt = $dbh->connect()->prepare($sql);
        $stmt->bindValue(':num', $num, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
    * Gets a list tile type names. May be extended to include other information
    */
    public static function getMapTileTypes() {
        $dbh = new Dbh();
        $sql = "SELECT id, name FROM map_tile_types ORDER BY id";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();

        $tileTypes = array();
        while ($row = $stmt->fetch()) {
            $tileInfo = array();
            $tileInfo['id'] = (int)$row['id'];
            $tileInfo['name'] = _($row['name']);
            array_push($tileTypes, $tileInfo);
        }

        return $tileTypes;
    }

    /**
     * Generates CSS class selectors that apply the appropriate background image for each map tile type.
     * @return string
     */
    public static function getMapTileStyles($tn=false):void {
        $dbh = new Dbh();
        $sql = "SELECT id, name, map_tile_type_id_image, UNIX_TIMESTAMP(updated_at) as timestamp FROM map_tile_types ORDER BY id";
        $stmt = $dbh->connect()->prepare($sql);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            // Account for tiles that use another tile's image
            if (!empty($row['map_tile_type_id_image'])) {
                $url = './images/tile/tile'.sprintf('%04d', (int)$row['map_tile_type_id_image']);
            } else {
                $url = './images/tile/tile'.sprintf('%04d', (int)$row['id']);
            }
            if ($tn) $url.='-tn';
            if ( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false || !empty($_COOKIE['useWebP']) )
            {
                $url.='.webp';
            } else {
                $url.='.png';
            }
            $url.='?u='.$row['timestamp'];

            echo '.mtt'.$row['id'].'{background-image:url('.$url.');}'."\n";
        }
    }

    /**
     * MapTile constructor. Needs a map_id, and x and y int or an associative array from a query of map_tiles.
     * @param arg either map_id or array
     * @param x - if querying with map_id, x coordinate
     * @param y - if querying with map_id, y coordinate
     * @throws Exception
     */
    public function __construct($arg, $x=null, $y=null)
    {
        if(is_int($arg) && is_int($x) && is_int($y)) {

            $mapId = $arg;
            $sql = "SELECT * FROM map_tiles_buildings_npcs_view WHERE map_id = ? AND x = ? AND y = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$mapId, $x, $y]);
            $count = $stmt->rowCount();
            // I suppose here I can use a try/catch to make this more user-friendly
            if ($count == 0) throw new InvalidMapTileException($mapId, $x, $y);
            
            // Now assign row to arg and we can use the same code as if
            // we passed the row to the constructor
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidMapTileException();
        }

        $this->mapId = (int)$arg['map_id'];
        $this->x = (int)$arg['x'];
        $this->y = (int)$arg['y'];
        $this->typeId = (int)$arg['map_tile_type_id'];
        // This should allow the map_tile_type_name to be localized if we have translations for the name
        $this->typeName = _($arg['map_tile_type_name']);
        $this->movementReq = (int)$arg['movement_req'];
        $this->skillIdReq = (is_null($arg['skill_id_req'])||$arg['skill_id_req']==0)?null:(int)$arg['skill_id_req'];
        if (!is_null($arg['map_tile_type_id_image']) && is_int($arg['map_tile_type_id_image']))
            $this->mapTileTypeIdImage = $arg['map_tile_type_id_image'];
        else $this->mapTileTypeIdImage=null;

        // Convert a list to an array
        if (isset($arg['npc_ids']) && strlen($arg['npc_ids'])) {
            $this->npcIds = explode(',', $arg['npc_ids']);
            // Convert the array to ints
            $this->npcIds = array_map('intval', $this->npcIds);
        } else $this->npcIds=array();

        // If there is a building object, add it to the tile object
        if (isset($arg['building_id']) && strlen($arg['building_id'])) {
            $this->building = new Building($arg);
        }

    }

    // Getters for all data
    public function getMapId() {return $this->mapId;}
    public function getX() {return $this->x;}
    public function getY() {return $this->y;}
    public function getTypeId() {return $this->typeId;}
    public function getTypeName() {return $this->typeName;}
    public function getMovementReq() {return $this->movementReq;}
    public function getSkillIdReq() {return $this->skillIdReq;}
    // If this is null, we assume the filename is tile000X.png (or webp if supported)
    // This is determined on the client-side
    public function getImageFilename() {return $this->mapTileTypeIdImage;}
    public function getScale() {return $this->scale;}
    public function getNpcIds() {return $this->npcIds;}

    /**
    * getClientTileData: Returns essential tile data for client-side interaction
    * 
    * I'm storing as a plain array for high transfer efficiency, so the values map to the following indicies:
    * 0: skillIdReq
    * 1: movementReq
    * 2: npcIds array
    * 3: building info array
    * 4: mapTileTypeIdImage
    *
    */
    public function getClientTileData(): array
    {
        $tileData = array();
        // The X and Y are redundant, they can be inferred by the element position in the array
        // Comment these out when not being used for testing
        // array_push($tileData, $this->x);
        // array_push($tileData, $this->y);
        array_push($tileData, $this->typeId);
        array_push($tileData, $this->skillIdReq);
        array_push($tileData, $this->movementReq);
        array_push($tileData, $this->npcIds);
        if (isset($this->building)) {
            array_push($tileData, $this->building->getClientBuildingData());
        } else array_push($tileData, null);
        // Tile background image
        array_push($tileData, $this->mapTileTypeIdImage);

        return $tileData;
    }


}
