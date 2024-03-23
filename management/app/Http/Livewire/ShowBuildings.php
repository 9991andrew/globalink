<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ShowBuildings extends DataTable
{
    public $objectClass = "Building";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'map_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.name' => '',
            'filters.id' => 'int',
        ]);
    }

    /**
     * To improve efficiency, we'll redefine the query to load professions so we don't have to do
     * repeated queries to get professions for each building
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('professions'), $this->filters);
        return $this->applySorting($query);
    }

}
