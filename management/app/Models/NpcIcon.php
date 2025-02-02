<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class NpcIcon extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'author'
    ];

    protected $appends = ['imageUrl', 'imageFilename', 'tnUrl', 'tnFilename'];

    /**
     * Returns the path (relative to the MEGA World game root folder) of this icon image.
     * Generally this would be used with the 'mega' Storage::disk
     * @param $png boolean Specifies that the returned image should be png instead of WebP
     * @param $tn boolean Return a smaller-sized preview "thumbnail" image
     * @return string
     */
    public function getImageFilenameAttribute($png=false, $tn=false) {
        if (is_null($this->id)) return '';
        $baseFilename = '/images/npc/npc'.sprintf('%04d', $this->id);
        if ($tn) $baseFilename.='-tn';
        return $baseFilename.($png?'.png':'.webp');
    }

    /**
     * Returns the URL for the icon image at the MEGA World game website.
     * @return string
     */
    public function getImageUrlAttribute($png=false, $tn=false)
    {
        // Note that a timestamp is added to the file to ensure the file is refreshed
        // after it is updated in the management interface.
        return Storage::disk('mega')
            ->url($this->getImageFilenameAttribute($png, $tn))
            .'?u='.(isset($this->updated_at)?$this->updated_at->timestamp:'');
    }

    /**
     * Returns a smaller-sized thumbnail image path
     */
    public function getTnUrlAttribute()
    {
        return $this->getImageUrlAttribute(false, true);
    }

    /**
     * Returns a smaller-sized thumbnail image path
     */
    public function getTnFilenameAttribute()
    {
        return $this->getImageFilenameAttribute(false, true);
    }

    public function npcs() {
        return $this->hasMany(Npc::class);
    }

}
