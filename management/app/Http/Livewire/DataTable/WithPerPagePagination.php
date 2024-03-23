<?php

namespace App\Http\Livewire\DataTable;

use Livewire\WithPagination;

trait WithPerPagePagination
{
    use WithPagination;

    public $perPage = 25;

    // This is a default that could be used, but it is also coded into the data-table blade component.
    public $pageOptions = array(
        10,
        25,
        100,
        500,
        1000,
        5000
    );

    public function mountWithPerPagePagination()
    {
        $this->perPage = session()->get('perPage', $this->perPage);
    }

    public function updatedPerPage($value)
    {
        session()->put('perPage', $value);
        $this->resetPage();
    }

    public function applyPagination($query)
    {
        return $query->paginate($this->perPage);
    }
}
