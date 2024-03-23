<?php

namespace App\Http\Livewire;
use App\Models\Map;
use Livewire\Component;
use Illuminate\Support\Str;

class ShowNpcs extends DataTable
{
    public $objectClass = "Npc";

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
            'filters.id' => 'int',
        ]);
    }

    /**
     * To improve efficiency, we'll redefine the query to load NPCs so we don't have to do
     * repeated queries to get map info
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with('map')->withCount('npcProfessions', 'npcPortals', 'npcItems', 'quests'), $this->filters);
        return $this->applySorting($query);
    }

}
