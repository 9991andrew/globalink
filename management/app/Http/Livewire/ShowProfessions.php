<?php

namespace App\Http\Livewire;

use App\Models\Map;
use Livewire\Component;
use Illuminate\Support\Str;

class ShowProfessions extends DataTable
{
    public $objectClass = "Profession";

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
     * To improve efficiency, we'll redefine the query to load the players count
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('players')->withCount('npcs'), $this->filters);
        return $this->applySorting($query);
    }

}
