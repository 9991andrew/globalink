<?php

namespace App\Http\Livewire;
use App\Models\Birthplace;
use App\Models\Map;


class ShowBirthplaces extends DataTable
{
    public $objectClass = "Birthplace";
    public $map_id = null;
    public $map_type_id = null;

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'map_id' => '',
        'map_type_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.name' => '',
        'filters.map_id' => 'required|int|in:'.Map::pluck('id')->implode(','),
    ]);}

    /**
     * I may need to get the count of players who have this birthplace. I cannot delete a birthplace unless
     * no players are using it. I am definitely not doing a cascade delete on the players associated with a birthplace.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with('map.mapType')->withCount('players'), $this->filters);
        return $this->applySorting($query);
    }

}
