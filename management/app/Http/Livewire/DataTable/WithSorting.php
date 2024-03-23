<?php

namespace App\Http\Livewire\DataTable;

trait WithSorting
{
    public $sorts = [];

    public function sortBy($field)
    {
        $direction = 'asc';
        if (isset($this->sorts[$field]) && $this->sorts[$field] === 'asc') $direction = 'desc';

        // Reorder the sort directions so the one just clicked is first
        $sorts[$field] = $direction;
        foreach ($this->sorts as $f => $dir) {
            if($f != $field) $sorts[$f] = $dir;
        }

        $this->sorts = $sorts;
    }

    public function applySorting($query)
    {
        foreach ($this->sorts as $field => $direction) {
            $query->orderBy($field, $direction);
        }

        return $query;
    }

}
