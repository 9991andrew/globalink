<?php

namespace App\Http\Livewire;
use App\Models\ItemCategory;


class ShowItemCategories extends DataTable
{
    public $objectClass = "ItemCategory";

    // Any filters we will use to search
    public $filters = [
        'id' => '',
        'name' => '',
        'disabled' => '',
    ];

    // Add any rules on top of the default ones
    protected function rules(): array
    {return array_merge(parent::rules(), [
        'filters.id' => 'numeric',
        'filters.disabled' => 'boolean',
    ]);}


    /**
     * Get the count of items that use each category
     */
    public function getRowsQueryProperty()
    {
        $query = $this->filter($this->objectClass::withCount('items'), $this->filters);
        return $this->applySorting($query);
    }

}
