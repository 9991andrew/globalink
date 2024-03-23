<?php

namespace App\Http\Livewire;
use App\Models\Map;
use Livewire\Component;
use Illuminate\Support\Str;

class ShowMapTypes extends DataTable
{
    public $objectClass = "MapType";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.name' => '',
        'filters.id' => 'int',
    ]);}


    /**
     * To improve efficiency, we'll redefine the query to load players so we don't have to do
     * repeated queries to get players for each user
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('maps'), $this->filters);
        return $this->applySorting($query);
    }


}
