<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'map_type_id'
    ];

    public function mapType()
    {
        return $this->belongsTo(MapType::class);
    }

    public function mapTiles()
    {
        return $this->hasMany(MapTile::class)->orderBy('x')->orderBy('y');
    }

    /**
     * Get the players currently on this map
     */
    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the NPCs that live on this map
     */
    public function npcs()
    {
        return $this->hasMany(Npc::class);
    }

    /**
     * Get the buildlings on this map
     */
    public function buildings()
    {
        return $this->hasMany(Building::class);
    }

    /**
     * Get buildings for which this map is a destination
     * Sometimes referred to as "Ingress Portals" as these are buildings
     * that allow players to enter this map From somewhere else
     */
    public function dest_maps()
    {
        return $this->hasMany(Building::class, 'dest_map_id');
    }


    /**
     * Get the birthplaces on this map
     */
    public function birthplaces()
    {
        return $this->hasMany(Birthplace::class);
    }

    /**
     * Create a 2D array of all the map tile type ids, like in MEGA World proper.
     */
    public function getTiles2DAttribute($simpleArray=false, $modelUrl=false) {
        $tiles = [];
        $row = [];
        $lastX = null;
        foreach($this->mapTiles as $tile)
        {
            if ($tile->x != $lastX) {
                if (sizeof($row)) {
                    array_push($tiles, $row);
                }
                $row = [];
            }
            // This almost certainly has to do a query for each tile, which isn't so efficient.
            // It'd be faster to use pluck, I think, but then I can't see what x is...
            // I could probably just count to the max x or maybe use pivot
            if ($simpleArray) {
                array_push($row, [$tile->mapTileType->id]);
            } else if($modelUrl) {
                array_push($row, $tile->mapTileType->modelURL);
            }
            else {
                array_push($row, $tile->mapTileType);
            }
            $lastX = $tile->x;
        }
        array_push($tiles, $row);

        return $tiles;
    }

    /**
     * Use a simplified version of tiles2D that just returns an array containing the tile type ID.
     * This is for compatibility with MEGA World game JS, which is optimized for extremely small data sizes.
     */
    public function getTiles2DJSAttribute()
    {
        return $this->getTiles2DAttribute(true,false);
    }


    /**
     * Use a simplified version of tiles2D that just returns an array containing the 3d tile url.
     */
    public function getModelUrlsAttribute()
    {
        return $this->getTiles2DAttribute(false,true);
    }



    /**
     * Renders base HTML for maps to show all tiles in context with the highest-available quality tiles.
     */
    public function getMapHTMLAttribute()
    {
        $mapTiles = $this->tiles2D;
        $tileString = "";
        $lenX = sizeof($mapTiles) - 1;
        $lenY = sizeof($mapTiles[0]) - 1;
        for ($x = 0; $x < sizeof($mapTiles); $x++) {
            for ($y = 0; $y < sizeof($mapTiles[0]); $y++) {
                $left = 'calc(var(--tileW)/2 * '.($x + $y).')';
                $topFactor = max($lenX, $lenY) + $x - $y;
                $top = 'calc(var(--tileH)/2 * '.$topFactor.')';
                $tile = $mapTiles[$x][$y];
                // As the tiles go "higher" vertically on the screen, z-index goes lower.
                $zIndex = (1 + ($x - $y) + max($lenY, $lenX)) * 2;

                $tileString .= '<div id="tilex'.$x.'y'.$y.'" class="tile x'.$x.' '.'y'.$y.' mtt'.$tile->id.'" style="left:'.$left.';top:'.$top.';z-index:'.$zIndex.'">';
                // tileString+=zIndex; // Show coordinates on the tile for debugging
                $tileString .= '</div>';
            }
        }
        return $tileString;
    }


}
