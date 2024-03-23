<?php

namespace App\Http\Livewire;
use Livewire\Component;

abstract class EditObject extends Component
{

    // This is better but requires PHP 7.4
    // public Player $editing;
    public $editing;
    public $showModal = false;
    // Set this to the name of the class we are working with
    public $objectClass;

    protected $listeners = [
        'updateEditing' => 'setEditing',
        'editorRefresh' => '$refresh',
        'editorPickerUpdate' => 'updatePickerItem',
        'cleanup' => 'cleanup',
    ];


    /**
     * Update a property of the editing object referred to by an item picker.
     * This will have to be overridden by the instance of EditObject (eg EditItem.php)
     * @param $selectedId
     */
    public function updatePickerItem($selectedId, $field)
    {
    }


    /**
     * This just does some cleanup to prevent weird 404 errors and such.
     */
    public function cleanup()
    {
        $this->editing = $this->makeBlankObject();
        $showModal = false;
    }

    /**
     * IMPORTANT:
     * Override this method for all objects with rules for the fields we want to edit.
     * @return string[]
     */
    public function rules(): array
    { return [
        'editing.name' => 'required',
    ];}

    public function mount() {
        $this->editing = $this->makeBlankObject();
        // This is used for quests
        $this->emit('typesetMathJax');
    }

    /**
     * If you want to specify defaults for new objects to be created, override this method.
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
        ]);
    }

    public function setEditing($objectId)
    {
        // A bit less elegant when you don't know in advance what the class will be
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

//    I don't think this is even used!
//    public function create()
//    {
//        if ($this->editing->getKey()) $this->editing = $this->makeBlankObject();
//    }

    public function save()
    {
        $this->validate();
        $this->editing->save();
        $this->showModal = false;
        $this->emit('rerenderParent');
        // Ensure the object is blank - if this isn't done
        // and this object is deleted, it will break the editor and show 404
        $this->cleanup();
    }

}
