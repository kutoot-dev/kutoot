<x-filament-panels::page>
    <form wire:submit="save" class="fi-sc-form space-y-6">
        {{ $this->form }}

        <x-filament::actions
            :actions="$this->getFormActions()"
        />
    </form>
</x-filament-panels::page>
