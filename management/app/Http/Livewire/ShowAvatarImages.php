<?php

namespace App\Http\Livewire;


use App\Models\AvatarImageType;
use App\Models\Gender;
use App\Models\Race;

class ShowAvatarImages extends DataTable
{
    public $objectClass = "AvatarImage";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'avatar_image_type_id' => '',
        'gender_id' => '',
        'race_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.name' => '',
        'filters.avatar_image_type_id' => 'int|in:'.AvatarImageType::pluck('id')->implode(','),
        'filters.gender_id' => 'int|in:'.Gender::pluck('id')->implode(','),
        'filters.race_id' => 'int|in:'.Race::pluck('id')->implode(','),
    ]);}

    /**
     * I may need to get the count of players who have this birthplace. I cannot delete a birthplace unless
     * no players are using it. I am definitely not doing a cascade delete on the players associated with a birthplace.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with('gender','race','avatarImageType')->withCount('players'), $this->filters);
        return $this->applySorting($query);
    }

}
