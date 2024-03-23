<?php

namespace App\Http\Livewire;


class ShowSkills extends DataTable
{
    public $objectClass = "Skill";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    /**
     * I may need to get the count of players who have this skill.
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('players')->withCount('mapTileTypes'), $this->filters);
        return $this->applySorting($query);
    }

}
