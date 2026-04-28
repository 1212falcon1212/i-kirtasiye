<?php

namespace App\Filament\Pages;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string $view = 'filament.pages.product-import';

    protected static ?string $navigationLabel = 'Ürün İçe Aktar';

    protected static ?string $title = 'JSON\'dan Ürün İçe Aktar';

    protected static ?string $navigationGroup = 'Ürün Yönetimi';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public bool $isImporting = false;

    public int $totalProducts = 0;
    public int $processedProducts = 0;
    public int $importedProducts = 0;
    public int $skippedProducts = 0;
    public int $errorProducts = 0;

    public array $importLog = [];

    private array $categoryCache = [];
    private array $brandCache = [];
    private int $brandsCreated = 0;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dosya Yükleme')
                    ->description('Ürünleri içeren JSON dosyasını yükleyin. Dosya en fazla 100MB olabilir.')
                    ->schema([
                        FileUpload::make('json_file')
                            ->label('JSON Dosyası')
                            ->acceptedFileTypes(['application/json'])
                            ->maxSize(102400) // 100MB
                            ->required()
                            ->helperText('Maksimum 100MB, JSON formatında'),
                        Checkbox::make('skip_images')
                            ->label('Resim indirmeyi atla')
                            ->helperText('Resimleri indirmeden hızlı import yapar'),
                        Checkbox::make('skip_existing')
                            ->label('Mevcut ürünleri atla')
                            ->default(true)
                            ->helperText('Aynı barkoda sahip ürünler varsa atlansın'),
                    ]),
            ])
            ->statePath('data');
    }

    public function startImport(): void
    {
        $this->validate();

        $data = $this->form->getState();

        if (empty($data['json_file'])) {
            Notification::make()
                ->title('Hata')
                ->body('Lütfen bir JSON dosyası yükleyin')
                ->danger()
                ->send();
            return;
        }

        $this->isImporting = true;
        $this->importLog = [];
        $this->processedProducts = 0;
        $this->importedProducts = 0;
        $this->skippedProducts = 0;
        $this->errorProducts = 0;
        $this->brandsCreated = 0;

        // Load categories and brands into cache
        $this->loadCategoryCache();
        $this->loadBrandCache();

        // Get the uploaded file
        $filePath = Storage::disk('local')->path($data['json_file']);

        if (!file_exists($filePath)) {
            $this->addLog('error', "Dosya bulunamadı: {$filePath}");
            $this->isImporting = false;
            return;
        }

        // Read JSON
        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (!$jsonData || !is_array($jsonData)) {
            $this->addLog('error', "JSON dosyası okunamadı veya geçersiz format");
            $this->isImporting = false;
            return;
        }

        // Support both flat array and nested {products: [...]} format
        $products = isset($jsonData['products']) ? $jsonData['products'] : $jsonData;

        if (!is_array($products) || empty($products)) {
            $this->addLog('error', "Ürün verisi bulunamadı");
            $this->isImporting = false;
            return;
        }

        $this->totalProducts = count($products);
        $this->addLog('info', "Toplam {$this->totalProducts} ürün bulundu");

        // Process products in batches
        $skipImages = $data['skip_images'] ?? false;
        $skipExisting = $data['skip_existing'] ?? true;

        foreach ($products as $index => $productData) {
            try {
                $this->importProduct($productData, $skipImages, $skipExisting);
            } catch (\Exception $e) {
                $this->errorProducts++;
                $this->addLog('error', "Hata: " . $e->getMessage());
            }

            $this->processedProducts++;

            // Log every 100 products
            if ($this->processedProducts % 100 === 0) {
                $this->addLog('info', "{$this->processedProducts}/{$this->totalProducts} işlendi");
            }
        }

        // Cleanup uploaded file
        Storage::disk('local')->delete($data['json_file']);

        $this->addLog('success', "İmport tamamlandı! Ürün: {$this->importedProducts}, Marka: {$this->brandsCreated}, Atlanan: {$this->skippedProducts}, Hata: {$this->errorProducts}");
        $this->isImporting = false;

        Notification::make()
            ->title('İmport Tamamlandı')
            ->body("Ürün: {$this->importedProducts}, Marka: {$this->brandsCreated}, Atlanan: {$this->skippedProducts}, Hata: {$this->errorProducts}")
            ->success()
            ->send();
    }

    private function loadCategoryCache(): void
    {
        $categories = Category::all();

        foreach ($categories as $category) {
            $key = mb_strtolower(trim($category->name));
            $this->categoryCache[$key] = $category->id;
            $this->categoryCache[$category->slug] = $category->id;
        }

        $this->addLog('info', count($this->categoryCache) . " kategori yüklendi");
    }

    private function findCategoryId(?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $key = mb_strtolower(trim($categoryName));
        return $this->categoryCache[$key] ?? null;
    }

    private function loadBrandCache(): void
    {
        $brands = Brand::all();

        foreach ($brands as $brand) {
            $key = mb_strtolower(trim($brand->name));
            $this->brandCache[$key] = $brand->id;
        }

        $this->addLog('info', count($this->brandCache) . " marka yüklendi");
    }

    private function findOrCreateBrand(?string $brandName): ?int
    {
        if (empty($brandName)) {
            return null;
        }

        $brandName = trim($brandName);
        $key = mb_strtolower($brandName);

        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }

        try {
            $brand = Brand::create([
                'name' => $brandName,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
            ]);

            $this->brandCache[$key] = $brand->id;
            $this->brandsCreated++;

            return $brand->id;
        } catch (\Exception $e) {
            $existingBrand = Brand::where('name', $brandName)->first();
            if ($existingBrand) {
                $this->brandCache[$key] = $existingBrand->id;
                return $existingBrand->id;
            }
            return null;
        }
    }

    private function importProduct(array $data, bool $skipImages, bool $skipExisting): void
    {
        $barcode = trim($data['barcode'] ?? '');

        if (empty($barcode)) {
            $this->skippedProducts++;
            return;
        }

        // Check existing
        if ($skipExisting && Product::where('barcode', $barcode)->exists()) {
            $this->skippedProducts++;
            return;
        }

        // Find category
        $categoryId = null;
        if (!empty($data['sub_category'])) {
            $categoryId = $this->findCategoryId($data['sub_category']);
        }
        if (!$categoryId && !empty($data['main_category'])) {
            $categoryId = $this->findCategoryId($data['main_category']);
        }

        // Download image
        $imagePath = null;
        if (!$skipImages && !empty($data['image_url'])) {
            $imagePath = $this->downloadImage($data['image_url'], $barcode);
        }

        // Find or create brand
        $brandName = trim($data['brand'] ?? '');
        $this->findOrCreateBrand($brandName);

        Product::create([
            'barcode' => $barcode,
            'name' => trim($data['name'] ?? ''),
            'brand' => $brandName,
            'manufacturer' => $brandName,
            'description' => null, // Description intentionally skipped
            'image' => $imagePath,
            'category_id' => $categoryId,
            'is_active' => true,
            'approval_status' => 'approved',
            'source' => 'json_import',
        ]);

        $this->importedProducts++;
    }

    private function downloadImage(string $url, string $barcode): ?string
    {
        try {
            $response = Http::timeout(5)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType = $response->header('Content-Type');
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = "products/{$barcode}.{$extension}";
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function addLog(string $type, string $message): void
    {
        $this->importLog[] = [
            'type' => $type,
            'message' => $message,
            'time' => now()->format('H:i:s'),
        ];
    }
}
