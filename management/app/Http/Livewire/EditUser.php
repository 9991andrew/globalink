<?php

namespace App\Http\Livewire;
use App\Models\Language;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;

class EditUser extends EditObject
{
    public $objectClass = 'User';
    public $passwordChange = null;
    public $passwordChanging = false;

    /**
     * Set all the rules so we can validate a player exists.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    { return [
        'editing.username' => ['required', 'unique:users,username,'.$this->editing->id, 'max:255'],
        'editing.email' => ['nullable', 'email', 'max:255'],
        'editing.locale_id' => ['required', 'in:'.Language::pluck('locale_id')->implode(',')],
        'passwordChange' => ['nullable', 'min:6'],
    ];}

    public function changePassword() {
        if (isset($this->passwordChanging)
            && isset($this->passwordChange)
            && strlen($this->passwordChange) >= 6 ) {
            $this->editing->password = hash('sha1', $this->passwordChange);
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
        $this->changePassword();
        parent::save();
    }

}
