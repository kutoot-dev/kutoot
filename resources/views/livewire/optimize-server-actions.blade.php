@php
    $canOptimize = auth()->check() && auth()->user()->hasRole('Super Admin');
@endphp
@if ($canOptimize)
<div class="flex items-center gap-1 ms-2">
    <button
        type="button"
        wire:click="optimizeClear"
        wire:loading.attr="disabled"
        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-btn-size-sm fi-btn-outlined gap-1.5 px-2 py-1.5 text-sm inline-grid shadow-sm bg-white dark:bg-white/5 fi-color-gray border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/10"
        title="Run php artisan optimize:clear"
    >
        <x-heroicon-o-trash class="h-4 w-4" />
        <span class="hidden sm:inline">Clear</span>
        <span wire:loading wire:target="optimizeClear" class="animate-spin">&#9696;</span>
    </button>
    <button
        type="button"
        wire:click="optimize"
        wire:loading.attr="disabled"
        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray fi-btn-size-sm fi-btn-outlined gap-1.5 px-2 py-1.5 text-sm inline-grid shadow-sm bg-white dark:bg-white/5 fi-color-gray border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/10"
        title="Run php artisan optimize"
    >
        <x-heroicon-o-bolt class="h-4 w-4" />
        <span class="hidden sm:inline">Optimize</span>
        <span wire:loading wire:target="optimize" class="animate-spin">&#9696;</span>
    </button>
</div>
@endif
