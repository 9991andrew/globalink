<?php

namespace App\Http\Livewire\DataTable;

trait WithBulkActions
{
    public $selectPage = false;
    public $allSelected = false;
    public $selected = [];

    public function renderingWithBulkActions()
    {

        if (count(array_intersect($this->pageRows, $this->selected)) == count($this->pageRows))
        {
            $this->selectPage = true;
        } else {
            $this->selectPage = false;
        }
    }

    /**
     * This adds every single result from the query (not just the page) to the selected array
     */
    public function selectAll()
    {
        $this->selected = $this->rowsQuery->pluck('id')->map(function ($id) {
            return (string)$id;
        })->toArray();;
        $this->allSelected = true;
    }

    public function getPageRowsProperty()
    {
        return $this->rows->pluck('id')->map(function ($id) {
            return (string)$id;
        })->toArray();
    }

    public function getSelectedRowsQueryProperty()
    {
        return $this->objectClass::whereKey($this->selected);
    }

}
