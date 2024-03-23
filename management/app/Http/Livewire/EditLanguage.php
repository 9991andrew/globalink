<?php

namespace App\Http\Livewire;
use App\Models\Language;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Str;

class EditLanguage extends EditObject
{
    public $objectClass = 'Language';

    /**
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    { return [
        'editing.name' => ['required', 'max:255'],
        'editing.native_name' => ['required', 'max:255'],
        'editing.country' => ['required', 'max:255'],
        'editing.locale_id' => ['required', 'max:19'],
        'editing.supported' => ['required', 'boolean'],
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
            'supported' => 0,
        ]);
    }


}
