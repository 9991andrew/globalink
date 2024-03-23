<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class ItemPicker extends Component
{
    use WithPagination;
    public $objectClass;
    public $totalMatches;
    // Specify the field name that this is for. Useful when there are multiple pickers in an editor
    public $field;
    public $count;
    public $searchMode = false;
    public $pageCount = 40;
    public $selectedId;
    public $selected; // The selected object
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    protected function rules(): array
    { return [
        'filters.id' => '',
        'filters.name' => '',
    ];}

    /**
     * Resets all filter elements to empty string
     */
    public function resetFilters()
    {
        foreach ($this->filters as $key => $value) {
            $this->filters[$key] = '';
        }
    }


    /**
     * Return the query with filters applied. This can be overridden in the specific DataTable
     * If some more fancy filtering rules need to be applied
     * @return mixed
     * @param $query
     * @param $params
     */
    public function filter($query, $params)
    {
        // Search all of these fields for a partial string match if any of them are filled in
        $partialFields = [
            'name',
            'username',
            'description',
        ];
        $blankObject = new $this->objectClass;
        $table = $blankObject->getTable();

        // Loop through other params limiting for any that have a value set
        foreach ($params as $param=>$value) {
            // Search on 'name' is handled specially, with wildcard and match on ID
            // This code is ugly, clean it up, do a loop or something
            if (in_array($param, $partialFields) && trim($value) !== '') {
                $value = trim($value);
                $query->where(function ($query) use(&$partialFields, &$value, &$blankObject, &$table) {
                    foreach ($partialFields as $partialField) {
                        if ($blankObject->getConnection()->getSchemaBuilder()->hasColumn($table, $partialField)) {
                            $query->orWhere($partialField, 'LIKE', '%'.$value.'%');
                        }
                    }
                });

                // Return the item with the exact ID if the value is an integer
                if ((int)$value > 0) $query->orWhere('id', $value);
                continue;
            }
            if (strlen(trim($value))) {
                // Handle explicit search for null fields only
                if ($value === '__NULL__') $query->whereNull($param);
                else {
                    $query->where($param, $value);
                }
            }
        }

        return $query;
    }

    public function getRowsQueryProperty()
    {
        return $this->filter($this->objectClass::query(), $this->filters);
    }

    public function unlimitedPages()
    {
        // Setting this to be too high causes serious issues...
        $this->pageCount = 200;
    }

    public function getRowsProperty()
    {
        $pagedQuery = $this->rowsQuery->paginate($this->pageCount);
        $this->totalMatches = $pagedQuery->total();
        $this->count = $pagedQuery->count();
        return $pagedQuery;

    }

    public function updatedFilters()
    {
        $this->searchMode = true;
    }

    public function updatedSelectedId()
    {
        //$this->filters['name'] = '';
        // Might have to have a separate operation for closing this... like clicking outside of bounds or hitting escape.
        // $this->searchMode = false;
        $this->selected = $this->objectClass::find($this->selectedId);
        $this->emit('editorPickerUpdate', $this->selectedId, $this->field);
    }

    public function mount()
    {

    }

    public function render()
    {
        if (isset($this->selectedId))
        {
            $this->selected = $this->objectClass::find($this->selectedId);
        }
        // To get the view name, is there a smarter way?
        return view("livewire.{$this::getName()}", [
            'objects' => $this->rows,
        ]);
    }
}
