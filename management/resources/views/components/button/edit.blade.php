<button {{ $attributes->merge(['type' => 'button', 'class' => 'min-w-[2.5rem] px-1 text-green-600 dark:text-green-400
hover:text-green-500 dark:hover:text-green-200
rounded focus:outline-none focus:border-gray-900 focus:ring ring-cyan-300 focus:ring-opacity-50
bg-green-600 bg-opacity-10 border border-green-500 border-opacity-20 hover:border-opacity-40
transition ease-in-out duration-150']) }}>
    @if(trim($slot)==="") <i class="fas fa-pencil-alt"></i> @else {{ $slot }} @endif
</button>
