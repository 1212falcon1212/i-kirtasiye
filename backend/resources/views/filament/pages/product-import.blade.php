<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Import Form --}}
        <x-filament::section>
            <x-slot name="heading">
                JSON Dosyasından Ürün Yükle
            </x-slot>
            <x-slot name="description">
                Ürünleri içeren JSON dosyasını yükleyin. Dosya barcode, name, brand, image_url, main_category ve sub_category alanlarını içermelidir.
            </x-slot>

            <form wire:submit="startImport">
                {{ $this->form }}

                <div class="mt-6">
                    <x-filament::button
                        type="submit"
                        :disabled="$isImporting"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="startImport">
                            <x-heroicon-m-arrow-up-tray class="w-5 h-5 mr-2 inline" />
                            İçe Aktarmayı Başlat
                        </span>
                        <span wire:loading wire:target="startImport">
                            <x-heroicon-m-arrow-path class="w-5 h-5 mr-2 inline animate-spin" />
                            İşleniyor...
                        </span>
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Progress --}}
        @if($isImporting || $processedProducts > 0)
            <x-filament::section>
                <x-slot name="heading">
                    İmport Durumu
                </x-slot>

                {{-- Progress Bar --}}
                @if($totalProducts > 0)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span>İşlenen: {{ $processedProducts }} / {{ $totalProducts }}</span>
                            <span>{{ $totalProducts > 0 ? round(($processedProducts / $totalProducts) * 100) : 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div
                                class="bg-primary-600 h-3 rounded-full transition-all duration-300"
                                style="width: {{ $totalProducts > 0 ? ($processedProducts / $totalProducts) * 100 : 0 }}%"
                            ></div>
                        </div>
                    </div>
                @endif

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $importedProducts }}</div>
                        <div class="text-sm text-green-700 dark:text-green-300">Eklenen</div>
                    </div>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $skippedProducts }}</div>
                        <div class="text-sm text-yellow-700 dark:text-yellow-300">Atlanan</div>
                    </div>
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $errorProducts }}</div>
                        <div class="text-sm text-red-700 dark:text-red-300">Hata</div>
                    </div>
                </div>

                {{-- Log --}}
                @if(count($importLog) > 0)
                    <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 max-h-64 overflow-y-auto font-mono text-sm">
                        @foreach($importLog as $log)
                            <div class="@if($log['type'] === 'error') text-red-400 @elseif($log['type'] === 'success') text-green-400 @else text-gray-300 @endif">
                                <span class="text-gray-500">[{{ $log['time'] }}]</span>
                                {{ $log['message'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
        @endif

        {{-- CLI Info --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                Alternatif: CLI Komutu
            </x-slot>
            <x-slot name="description">
                Büyük dosyalar için CLI komutunu kullanabilirsiniz (timeout sorunu yaşamaz)
            </x-slot>

            <div class="bg-gray-900 dark:bg-gray-950 rounded-lg p-4 font-mono text-sm text-gray-300">
                <p class="mb-2"># Temel kullanım:</p>
                <code class="text-green-400">php artisan products:import ../kozvit_products.json</code>

                <p class="mt-4 mb-2"># Resim indirmeden hızlı import:</p>
                <code class="text-green-400">php artisan products:import ../kozvit_products.json --skip-images</code>

                <p class="mt-4 mb-2"># Test modu (veritabanına eklemez):</p>
                <code class="text-green-400">php artisan products:import ../kozvit_products.json --dry-run</code>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
