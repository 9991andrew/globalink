<?php

namespace App\Http\Livewire;
use App\Models\ManagementUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Illuminate\Support\Str;

class EditManagementUser extends EditObject
{
    public $objectClass = 'ManagementUser';
    public $passwordChange = null;
    public $passwordChanging = false;

    /**
     * Set all the rules so we can validate a player exists.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    { return [
        'editing.name' => ['required', 'unique:management_users,name,'.$this->editing->id, 'max:255'],
        'editing.email' => ['nullable', 'unique:management_users,email,'.$this->editing->id, 'max:255'],
        'editing.time_zone' => ['required'],
        'passwordChange' => ['nullable', 'min:7'],
    ];}

    /**
     * If you want to specify defaults for new objects to be created, override this method.
     * @return mixed
     */
    public function makeBlankObject()
    {
        return $this->objectClass::make([
            'id' => '',
            'name' => '',
            'time_zone' => 'America/Edmonton'
        ]);
    }

    public function changePassword() {
        if (isset($this->passwordChanging)
            && isset($this->passwordChange)
            && strlen($this->passwordChange) >= 7 ) {
            $this->editing->password = Hash::make($this->passwordChange);
        }
    }

    public function generatePassword() {
        $this->passwordChange = strtoupper(Str::random(4)).'_'.str_pad(random_int(0, 9999), 4, '0').'_'.(strtolower(Str::random(4)));
    }

    /**
     * Ensure the password change stuff is reset when we set a user
     */
    public function setEditing($objectId)
    {
        $this->passwordChange = null;
        $this->passwordChanging = false;
        parent::setEditing($objectId);
    }

    /**
     * Override the save() method so we can handle password validation
     */
    public function save()
    {
        $this->editing->name = trim($this->editing->name);
        $this->editing->email = trim($this->editing->email);
        // Record who created the user
        if (!isset($this->editing->id)) {
            $this->editing->created_by = Auth::user()->name;
        }
        $this->changePassword();
        parent::save();
    }

}
