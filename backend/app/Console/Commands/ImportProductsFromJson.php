<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProductsFromJson extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'products:import
                            {file? : JSON dosya yolu (varsayılan: ../kozvit_products.json)}
                            {--chunk=100 : Her seferde işlenecek ürün sayısı}
                            {--skip-images : Resim indirmeyi atla}
                            {--dry-run : Gerçek ekleme yapmadan test et}';

    /**
     * The console command description.
     */
    protected $description = 'JSON dosyasından ürünleri içe aktar';

    private array $categoryCache = [];
    private array $brandCache = [];
    private int $imported = 0;
    private int $skipped = 0;
    private int $errors = 0;
    private int $brandsCreated = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->argument('file') ?? base_path('../kozvit_products.json');
        $chunkSize = (int) $this->option('chunk');
        $skipImages = $this->option('skip-images');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("Dosya bulunamadı: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("JSON dosyası okunuyor: {$filePath}");

        // Load categories and brands into cache
        $this->loadCategoryCache();
        $this->loadBrandCache();

        // Read JSON file
        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (!$jsonData || !is_array($jsonData)) {
            $this->error("JSON dosyası okunamadı veya geçersiz format");
            return Command::FAILURE;
        }

        // Support both flat array and nested {products: [...]} format
        $products = isset($jsonData['products']) ? $jsonData['products'] : $jsonData;

        if (!is_array($products) || empty($products)) {
            $this->error("Ürün verisi bulunamadı");
            return Command::FAILURE;
        }

        $totalProducts = count($products);
        $this->info("Toplam {$totalProducts} ürün bulundu");

        if ($dryRun) {
            $this->warn("DRY-RUN modu aktif - veritabanına ekleme yapılmayacak");
        }

        // Create progress bar
        $bar = $this->output->createProgressBar($totalProducts);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
        $bar->setMessage('Başlatılıyor...');
        $bar->start();

        // Process in chunks
        $chunks = array_chunk($products, $chunkSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $productData) {
                    $bar->setMessage($productData['name'] ?? 'İşleniyor...');

                    if (!$dryRun) {
                        $this->importProduct($productData, $skipImages);
                    } else {
                        // Dry run - just validate
                        if (!empty($productData['barcode'])) {
                            $this->imported++;
                        } else {
                            $this->skipped++;
                        }
                    }

                    $bar->advance();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->errors++;
                $this->error("\nChunk {$chunkIndex} hatası: " . $e->getMessage());
            }

            // Clear memory
            gc_collect_cycles();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("=== İMPORT ÖZETI ===");
        $this->info("✓ Eklenen Ürün: {$this->imported}");
        $this->info("✓ Eklenen Marka: {$this->brandsCreated}");
        $this->warn("○ Atlanan (barkod mevcut): {$this->skipped}");
        $this->error("✗ Hata: {$this->errors}");

        return Command::SUCCESS;
    }

    /**
     * Load categories into memory cache
     */
    private function loadCategoryCache(): void
    {
        $this->info("Kategoriler yükleniyor...");

        $categories = Category::all();

        foreach ($categories as $category) {
            // Cache by name (lowercase, trimmed)
            $key = mb_strtolower(trim($category->name));
            $this->categoryCache[$key] = $category->id;

            // Also cache by slug
            $this->categoryCache[$category->slug] = $category->id;
        }

        $this->info("✓ " . count($this->categoryCache) . " kategori önbelleğe alındı");
    }

    /**
     * Load brands into memory cache
     */
    private function loadBrandCache(): void
    {
        $this->info("Markalar yükleniyor...");

        $brands = Brand::all();

        foreach ($brands as $brand) {
            // Cache by name (lowercase, trimmed)
            $key = mb_strtolower(trim($brand->name));
            $this->brandCache[$key] = $brand->id;
        }

        $this->info("✓ " . count($this->brandCache) . " marka önbelleğe alındı");
    }

    /**
     * Find or create brand by name
     */
    private function findOrCreateBrand(?string $brandName): ?int
    {
        if (empty($brandName)) {
            return null;
        }

        $brandName = trim($brandName);
        $key = mb_strtolower($brandName);

        // Check cache first
        if (isset($this->brandCache[$key])) {
            return $this->brandCache[$key];
        }

        // Create new brand
        try {
            $brand = Brand::create([
                'name' => $brandName,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
            ]);

            // Add to cache
            $this->brandCache[$key] = $brand->id;
            $this->brandsCreated++;

            return $brand->id;
        } catch (\Exception $e) {
            // If brand creation fails (maybe duplicate), try to find it
            $existingBrand = Brand::where('name', $brandName)->first();
            if ($existingBrand) {
                $this->brandCache[$key] = $existingBrand->id;
                return $existingBrand->id;
            }
            return null;
        }
    }

    /**
     * Find category ID by name
     */
    private function findCategoryId(?string $categoryName): ?int
    {
        if (empty($categoryName)) {
            return null;
        }

        $key = mb_strtolower(trim($categoryName));

        return $this->categoryCache[$key] ?? null;
    }

    /**
     * Import a single product
     */
    private function importProduct(array $data, bool $skipImages): void
    {
        $barcode = trim($data['barcode'] ?? '');

        // Barcode is required
        if (empty($barcode)) {
            $this->skipped++;
            return;
        }

        // Check if product already exists
        $existingProduct = Product::where('barcode', $barcode)->first();

        if ($existingProduct) {
            $this->skipped++;
            return;
        }

        // Find category
        $categoryId = null;

        // Try sub_category first (more specific)
        if (!empty($data['sub_category'])) {
            $categoryId = $this->findCategoryId($data['sub_category']);
        }

        // Fall back to main_category
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

        // Create product
        try {
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
                'source' => 'kozvit_import',
            ]);

            $this->imported++;
        } catch (\Exception $e) {
            $this->errors++;
            \Log::error("Product import error for barcode {$barcode}: " . $e->getMessage());
        }
    }

    /**
     * Download image from URL
     */
    private function downloadImage(string $url, string $barcode): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);

            if (!$response->successful()) {
                return null;
            }

            // Determine extension from content type
            $contentType = $response->header('Content-Type');
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            // Save to storage
            $filename = "products/{$barcode}.{$extension}";
            Storage::disk('public')->put($filename, $response->body());

            return $filename;
        } catch (\Exception $e) {
            // Silent fail for images - not critical
            return null;
        }
    }
}