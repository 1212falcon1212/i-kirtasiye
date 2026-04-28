<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Kaydet
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Desi Bazlı Kargo Ücretleri
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Her kargo firması için desi aralıklarına göre fiyatları belirleyin.
        </p>
        {{ $this->table }}
    </div>
</x-filament-panels::page>