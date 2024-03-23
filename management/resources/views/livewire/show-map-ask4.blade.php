<div style="margin-left: 30%" :data="$data">
    <br />
    <h1>Courses available/selected for Map "{{ $data['mapName'] }}"</h1>
    <br />

    <form id="courses-form" wire:submit.prevent="save" method="POST">
        <x-input.group for="courses" label="Select or unselect courses:  ">
            <select id="courses" wire:model="course_name" multiple>
                @foreach($data['availableCourses'] as $v)
                        <option value="{{ $v }}">{{ $v }}</option>
                @endforeach
            </select>
        </x-input.group>

        <br />
        <x-button class="highlight" type="submit">Save</x-button>
        &nbsp&nbsp
        <x-button onclick="window.location='/maps'">Cancel</x-button>
    </form>
</div>
