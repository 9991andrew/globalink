<?php

namespace App\Http\Livewire;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\WithFileUploads;

class EditItemIcon extends EditObject
{
    use WithFileUploads;
    public $objectClass = 'ItemIcon';
    public $newImage;
    public $iteration;

    /**
     * Set all the rules so we can validate a birthplace.
     * For a field to be editable at all it has to appear here.
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'editing.name' => ['required', 'max:200'],
            'editing.author' => ['nullable', 'max:200'],
            'newImage' => ['nullable', 'image', 'max:800'], //max file size in KB
        ];
    }

    public function updatedNewImage()
    {
        $this->validate(['newImage' => 'nullable|image|max:1000']);
    }

    public function setEditing($objectId)
    {
        $this->newImage = null;
        $this->iteration++;
        // A bit less elegant when you don't know in advance what the class will be
        if (is_numeric($objectId)) {
            $this->editing = $this->objectClass::find($objectId);
        } else {
            $this->editing = $this->makeBlankObject();
        }

        $this->showModal = true;
    }

    /**
     * EditIcon needs a custom save function to handle the image upload, if there was one.
     */
    public function save()
    {
        $this->validate();
        $this->editing->save();

        // If an image was uploaded, name it as per the convention for icons.
        // Overwrite the old one if necessary
        if (isset($this->newImage)) {
            // Get the filename without an extension
            $filenameBase = preg_replace('/(.*)(\..*)$/', '$1', $this->editing->imageFilename);
            $filename = Storage::disk('mega')->path($filenameBase.'.png');
            // Store the file as a PNG, regardless of the format it was submitted as.
            $png = Image::make($this->newImage)->save($filename, 100, 'png');
            Image::make($this->newImage)->save();
            // webp filename
            $filename = Storage::disk('mega')->path($filenameBase.'.webp');
            $webp = Image::make($this->newImage)->save($filename, 94, 'webp');
            // Create compact thumbnails
            $tn = Image::make($this->newImage)->resize(
                128,
                128,
                function($constraint) {
                    // preserve aspect ratio
                    $constraint->aspectRatio();
                    // Do not increase the size if the file was smaller to begin with
                    $constraint->upsize();

                });
            // png thumbnail
            $filename = Storage::disk('mega')->path($filenameBase.'-tn.png');
            $tn->save($filename, 90, 'png');

            // webp thumbnail
            $filename = Storage::disk('mega')->path($filenameBase.'-tn.webp');
            $tn->save($filename, 94, 'webp');

            $this->editing->updated_at = now();
            $this->editing->save();
            $this->newImage = null;
        }

        $this->iteration++;
        $this->showModal = false;
        $this->emit('rerenderParent');
    }

}
