<?php

namespace App\Http\Livewire;

use App\Http\Livewire\DataTable\WithSorting;
use App\Http\Livewire\DataTable\WithBulkActions;
use App\Http\Livewire\DataTable\WithPerPagePagination;

// Why do I need this? Shouldn't that be automatic?
use App\Models\PlayerQuestsLog;
use App\Models\QuestPrerequisiteProfession;
use Illuminate\Support\Str;
use Livewire\Component;


abstract class DataTable extends Component
{
    use WithPerPagePagination, WithBulkActions, WithSorting;
    public $showConfirm = false;
    public $objectClass;
    public $title; // Defaults to the pluralized objectClass
    public $confirmId = null;
    public $confirmClass = null;
    public $pivot = null;
    public $pivotId = null;
    protected $queryString = ['sorts','filters'];
    public $filters = [
        'id' => '',
        'name' => '',
    ];

    /**
     * Resets all filter elements to empty string
     */
    public function resetFilters()
    {
        foreach ($this->filters as $key => $value) {
            $this->filters[$key] = '';
        }
    }

    // Livewire complains if this isn't here.
    protected function rules(): array
    { return [
        'filters.id' => '',
        'filters.name' => '',
        'perPage' => 'required|numeric',
    ];}

    protected $listeners = ['rerenderParent'];

    public function rerenderParent()
    {
        $this->mount();
        $this->render();
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function updatedFilters()
    {
        $this->filters['id'] = null;
        $this->resetPage();
    }

    /**
     * Use the public properties to delete some item specified by the form.
     * This allows this function to delete object types aside from the one this form is for
     * (eg Players can be deleted from the User view)
     */
    public function delete()
    {
        if (!isset($this->confirmClass)) {
            $this->confirmClass = $this->objectClass;
        }

        // If we're using a pivot relationship we will handle the delete with a detach command
        if (isset($this->pivot) && strlen($this->pivot)) {
            $pivot = $this->pivot;
            $this->confirmClass::find($this->confirmId)->$pivot()->detach($this->pivotId);

            // If a player quest is being deleted it's also necessary to remove it from the logs to ensure the game
            // will allow the player to take the quest again.
            if ($this->confirmClass=="Player" && $pivot == "quests") {
                PlayerQuestsLog::where('player_id', $this->confirmId)->where('quest_id', $this->pivotId)->delete();
            }

        } else if (isset($this->confirmId) && (int)$this->confirmId > 0) {
            $this->confirmClass::find($this->confirmId)->delete();
            // Ensure this element isn't selected (since it's now gone)
            // There are probably more efficient ways to do this,
            // but for some reason livewire was returning an object instead of array
            // when I used array_filter() and unset().
            if (($key = array_search($this->confirmId, $this->selected)) !== false) {
                $newArray = array();
                foreach($this->selected as $el) {
                    if ($el != $this->confirmId) array_push($newArray, $el);
                }
                $this->selected = $newArray;
            }

            $this->confirmId = null;
        } else {
            // If confirmIds is empty, delete selected items.
            // Is this dangerous? You could delete the wrong thing if you goof
            $this->deleteSelected();
        }
        $this->showConfirm = false;

        // Make the child editor component refresh but only if
        // we deleted something that wasn't the objectClass.
        // This fixes a bug where sometimes deleting something will break the editor until a page reload.
        if ( $this->confirmClass != $this->objectClass || (isset($this->pivot) && strlen($this->pivot) > 0) ) {
            $this->emit('editorRefresh');
        }
        else $this->emit('resetEditing');
    }

    public function deleteSelected()
    {
        $this->selectedRowsQuery->delete();
        $this->selected = [];
        $this->selectPage = false;
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
            'native_name',
            'country',
            'description',
            'old_id',
            'prologue',
            'content',
            'target_npc_prologue',
            'success_words',
            'failure_words',
            'result',
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
            } if ( $param == 'quest_profession_id' && strlen(trim($value)) ) {
                // Get the list of quest_ids that use this profession
                if ($value=="__NULL__") {
                    $query->whereNotIn('id', QuestPrerequisiteProfession::pluck('quest_id'));
                } else {
                    $quest_ids = QuestPrerequisiteProfession::where('profession_id', $value)->pluck('quest_id');
                    $query->whereIn('id', $quest_ids);
                }
            } else if (strlen(trim($value))) {
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
        // TODO: There is a bug deleting when using sort by profession count in players. Not sure how to fix.
        $query = $this->filter($this->objectClass::query(), $this->filters);
        return $this->applySorting($query);
    }

    public function getRowsProperty()
    {
        return $this->applyPagination($this->rowsQuery);
    }

    public function mount()
    {
        if (!isset($this->title)) {
            $this->title = Str::plural(camelToSpaced($this->objectClass));
        }
    }

    public function render()
    {
        // To get the view name, is there a smarter way?
        return view("livewire.{$this::getName()}", [
            'objects' => $this->rows,
        ]);
    }


}
