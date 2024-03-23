<?php

namespace App\Http\Livewire;

use App\Models\Item;
use App\Models\ItemCategory;
use Livewire\Component;

class ShowItems extends DataTable
{
    public $objectClass = "Item";
    public $name = null;
    public $category_id = null;

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'item_category_id' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.name' => '',
        'filters.item_category_id' => 'required|int|in:'.ItemCategory::pluck('id')->implode(','),
    ]);}

    /**
     * To improve efficiency, we'll redefine the query to load associated records so we don't have to do
     * repeated queries to get these
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::with(['itemCategory', 'gender', 'race'])->withCount(['players', 'quests', 'tools']), $this->filters);
        return $this->applySorting($query);
    }

}

