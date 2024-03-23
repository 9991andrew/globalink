<?php

namespace App\Http\Livewire;

class ShowNpcIcons extends DataTable
{
    public $objectClass = "NpcIcon";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {
        return array_merge(parent::rules(), [
            'filters.name' => '',
            'filters.id' => 'numeric',
        ]);
    }

    /**
     * To improve efficiency, we'll redefine the query to load professions so we don't have to do
     * repeated queries to get professions for each user
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('npcs'), $this->filters);
        return $this->applySorting($query);
    }

}
