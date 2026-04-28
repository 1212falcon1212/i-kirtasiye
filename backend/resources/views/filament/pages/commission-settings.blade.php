<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Hizmet Bedeli Ayarları
        </x-slot>
        <x-slot name="description">
            Platform genelinde geçerli sabit hizmet bedeli oranı
        </x-slot>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit">
                    Kaydet
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
