@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        @isset($title)
        <div class="text-xl">
            {{ $title }}
        </div>
        @endisset

        <div class="mt-4">
            {{ $content }}
        </div>
    </div>

    <div class="flex justify-end space-x-4 px-6 py-4 bg-gray-100 dark:bg-gray-700">
        {{ $footer }}
    </div>
</x-modal>
