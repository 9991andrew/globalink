<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MapTileType extends Model
{
    use HasFactory;

    protected $appends = ['imageUrl', 'tnUrl', 'imageFilename', 'modelFileName', 'modelURL'];

    protected $fillable = [
        'name',
        'movement_req',
        'skill_id_req',
        'map_tile_type_id_image',
        'author',
    ];

    public function maps()
    {
        return $this->hasMany(Map::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id_req');
    }

    public function getImageFilenameAttribute($tn=false)
    {
        if (is_null($this->id)) return '';
        if (is_null($this->map_tile_type_id_image) || strlen($this->map_tile_type_id_image) == 0) {
            $fileBase = 'tile'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
            $fileName = $fileBase . '.obj';
        } else {
            // Ensure the extension is stripped off (we use the extension field for this)
            $fileBase = 'tile'.str_pad($this->map_tile_type_id_image, 4, '0', STR_PAD_LEFT);
        }
        if ($tn) $fileBase .= '-tn';
        // TODO: Implement webp detection. For now I'm assuming webp support.
        return '/images/tile/'.$fileBase.'.webp';
    }

    /**
     * Gets the actual public path to the file used for image tags
     * @param $scale int Specify the desired scale. 1 small. Defaults to the largest available (typically 2).
     */
    public function getImageUrlAttribute($tn=false)
    {
        if (is_null($this->id)) return '';
        return Storage::disk('mega')->url($this->getImageFilenameAttribute($tn).'?u='.(isset($this->updated_at)?$this->updated_at->timestamp:''));
    }

    /**
     * Alias to getImageUrlAttribute that returns a lower resolution tile
     * @return string
     */
    public function getTnUrlAttribute()
    {
        return $this->getImageUrlAttribute(true);
    }


    public function getModelFileNameAttribute()
    {
        // Ensure the extension is stripped off (we use the extension field for this)
        if (is_null($this->id)) return '';

        $fileBase = 'tile' . str_pad($this->id, 4, '0', STR_PAD_LEFT);
        

        return '/models/tile/' . $fileBase . '.gltf';
    }

    /**
     * Gets the actual public path to the file used for image tags
     * @param $scale int Specify the desired scale. 1 small. Defaults to the largest available (typically 2).
     */
    public function getModelUrlAttribute()
    {
        if (is_null($this->id)) return '';
        return Storage::disk('mega')->url($this->getModelFileNameAttribute() . '?u=' . (isset($this->updated_at) ? $this->updated_at->timestamp : ''));
    }

}
