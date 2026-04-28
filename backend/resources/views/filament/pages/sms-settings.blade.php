<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>