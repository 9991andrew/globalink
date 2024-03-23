<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Map;
use App\Models\MapTile;
use App\Models\Npc;
use Illuminate\Http\Request;

class MapTileController extends Controller
{
    public function update(Map $map, Request $request)
    {
        $x = $request->input('x');
        $y = $request->input('y');
        $tile = MapTile::where('map_id', $map->id)->where('x', $x,)->where('y', $y)->first();
        $tile->map_tile_type_id = $request->input('mapTileTypeId');
        $tile->save();
    }

    /**
     * Returns JSON string with metadata about what is on this tile
     * @param Map $map
     * @param Request $request
     */
    public function info(Map $map, Request $request)
    {
        $x = $request->input('x');
        $y = $request->input('y');
        $tile = MapTile::where('map_id', $map->id)->where('x', $x,)->where('y', $y)->first();
        $npcs = Npc::where('map_id', $map->id)
            ->whereRaw($x.' >= LEAST(x_left,x_right) AND '.$x.' <= GREATEST(x_left,x_right)')
            ->whereRaw($y.' >= LEAST(y_top,y_bottom) AND '.$y.' <= GREATEST(y_top,y_bottom)')
            ->get();

        $buildings = Building::where('map_id', $map->id)->where('x', $x)->where('y', $y)->get();

        $data = [
            'tileTypeName' => $tile->mapTileType->name,
            'movement_req' => $tile->mapTileType->movement_req,
            'skill_id_req' => $tile->mapTileType->skill_id_req,
            'skill_name' => (isset($tile->mapTileType->skill_id_req)?$tile->mapTileType->skill->name:''),
            'npcs' => $npcs->toArray(),
            'buildings' => $buildings->toArray(),
        ];

        return response()->json($data);




    }

}
