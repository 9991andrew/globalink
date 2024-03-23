<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'level',
        'map_id',
        'x',
        'y',
        'dest_map_id',
        'dest_x',
        'dest_y',
        'external_link',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function destMap()
    {
        return $this->belongsTo(Map::class, 'dest_map_id');
    }

    public function professions()
    {
        return $this->belongsToMany(Profession::class, 'building_professions')->withTimestamps();
    }

}
