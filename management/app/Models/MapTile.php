<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapTile extends Model
{
    use HasFactory;

    /**
     * To keep the model size small, we don't bother timestamping each tile.
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'map_id',
        'x',
        'y',
        'map_tile_type_id'
    ];

    public function mapTileType()
    {
        return $this->belongsTo(MapTileType::class);
    }

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}
