<?php

namespace App\Http\Livewire;

use App\Models\Map;
use App\Models\MapTile;
use Livewire\Component;

class MapTileEditor extends Component
{
    // Interesting, if you type this, it automatically assigns the map to it without needing mount.
    // I'll do it this way for PHP 7.3 compatibility.
    public $map;
    public $currentX=0;
    public $currentY=0;
    public $mapTilesJSON;

    public function mount(Map $map)
    {
        $this->map = $map;
    }

    // Tile updating is done with a separate controller via fetch requests
    // because using livewire was too slow and you had to wait a beat between every update.

    /**
     * Creates the initial tile if one doesn't exist yet.
     */
    public function addInitial()
    {
        if (MapTile::where('map_id', $this->map->id)->count() == 0) {
            $mapTile = new MapTile;
            $mapTile->map_id=$this->map->id;
            $mapTile->x = 0;
            $mapTile->y = 0;
            $mapTile->map_tile_type_id=1;
            $mapTile->save();
        }
    }

    /**
     * Increases the X range of the map, adding default "Plain" tiles
     */
    public function expandX()
    {
        $this->addInitial();
        $maxX = MapTile::where('map_id', $this->map->id)->max('x');
        $maxY = MapTile::where('map_id', $this->map->id)->max('y');
        // Add new X
        for($i=0; $i<=$maxY; $i++) {
            $mapTile = new MapTile;
            $mapTile->map_id=$this->map->id;
            $mapTile->x = $maxX+1;
            $mapTile->y = $i;
            $mapTile->map_tile_type_id=1;
            $mapTile->save();
        }

        $this->map = Map::find($this->map->id);
    }

    /**
     * Increases the Y range of the map, adding default "Plain" tiles
     */
    public function expandY()
    {
        $this->addInitial();
        $maxX = MapTile::where('map_id', $this->map->id)->max('x');
        $maxY = MapTile::where('map_id', $this->map->id)->max('y');
        // Add new Y
        for($i=0; $i<=$maxX; $i++) {
            $mapTile = new MapTile;
            $mapTile->map_id=$this->map->id;
            $mapTile->x = $i;
            $mapTile->y = $maxY+1;
            $mapTile->map_tile_type_id=1;
            $mapTile->save();
        }

        $this->map = Map::find($this->map->id);
    }


    /**
     * Reduces the X range of the map, deleting a column of tiles
     */
    public function reduceX()
    {
        $maxX = MapTile::where('map_id', $this->map->id)->max('x');
        // Delete an entire column at the max X
        MapTile::where('map_id', $this->map->id)->where('x', $maxX)->delete();
        $this->map = Map::find($this->map->id);
    }

    /**
     * Reduces the Y range of the map, deleting a row of tiles
     */
    /**
     * Reduces the X range of the map, deleting a column of tiles
     */
    public function reduceY()
    {
        $maxY = MapTile::where('map_id', $this->map->id)->max('y');
        // Delete an entire column at the max X
        MapTile::where('map_id', $this->map->id)->where('y', $maxY)->delete();
        $this->map = Map::find($this->map->id);
    }

    public function render()
    {
        $this->mapTilesJSON = json_encode($this->map->tiles2DJS);
        return view('livewire.map-tile-editor');
    }
}
