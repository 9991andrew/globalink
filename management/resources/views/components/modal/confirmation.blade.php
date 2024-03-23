@props(['id' => null])

<div
    x-init="$watch('showConfirm', value => value && setTimeout(autofocus, 50))"
    x-on:close.stop="showConfirm = false"
    x-on:keydown.escape.window="showConfirm = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    x-show="showConfirm"
    @isset($id) id="{{ $id }}" @endisset
    class=""
    style="display: none;"
>
    <div x-show="showConfirm" class="fixed inset-0 transform transition-all" x-on:click="showConfirm = false" x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="absolute inset-0 bg-black opacity-50"></div>
    </div>

    <div x-show="showConfirm"
         class="z-50 fixed top-1/3 inset-x-5 sm:inset-x-5 max-h-full max-w-full sm:max-w-lg overflow-scroll m-auto bg-white dark:bg-gray-800 rounded-lg shadow-2xl transform transition-all"
         style="max-height:calc(100% - 2.5rem);"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        <div maxWidth="sm" {{ $attributes }}>
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-4 sm:pb-4">
                <div class="sm:flex sm:items-start">

                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-bold">
                            <i class="fas fa-exclamation-triangle text-red-500"></i> &nbsp; {{ $title }}
                        </h3>

                        <div class="mt-2 font-base">
                            {{ $content }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4 px-6 py-4 bg-gray-100 dark:bg-gray-700">
                {{ $footer }}
            </div>
        </div>
    </div>

</div>
