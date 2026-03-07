<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Integration Status
        </x-slot>
        <x-slot name="description">
            Configuration health check — green means all required settings are present.
        </x-slot>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($integrations as $name => $info)
                <div class="rounded-lg border p-4 {{ $info['configured'] ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950' }}">
                    <div class="flex items-center gap-2">
                        @if ($info['configured'])
                            <x-heroicon-o-check-circle class="h-5 w-5 text-green-600 dark:text-green-400" />
                        @else
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                        @endif
                        <span class="text-sm font-semibold capitalize {{ $info['configured'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ str_replace('_', ' ', $name) }}
                        </span>
                    </div>
                    @unless ($info['configured'])
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                            Missing: {{ implode(', ', $info['missing']) }}
                        </p>
                    @endunless
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
