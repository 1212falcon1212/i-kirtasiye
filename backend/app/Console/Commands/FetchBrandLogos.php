<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchBrandLogos extends Command
{
    protected $signature = 'brands:fetch-logos
                            {--dry-run : Sadece URL bul, indirme}
                            {--force : Mevcut logolari da yeniden indir}
                            {--brand= : Tek bir marka adi icin calistir}
                            {--delay=2500 : Istekler arasi bekleme suresi (ms)}';

    protected $description = 'Marka logolarini Google Images scraping ile otomatik indir';

    private int $found = 0;
    private int $notFound = 0;
    private int $failed = 0;
    private array $missingBrands = [];
    private array $foundBrands = [];

    public function handle(): int
    {
        $query = Brand::where('is_active', true);

        if ($this->option('brand')) {
            $query->where('name', 'like', '%' . $this->option('brand') . '%');
        }

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('logo_url')->orWhere('logo_url', '');
            });
        }

        $brands = $query->orderBy('name')->get();

        if ($brands->isEmpty()) {
            $this->info('Logosuz marka bulunamadi.');
            return self::SUCCESS;
        }

        $delay = (int) $this->option('delay');
        $this->info("Toplam {$brands->count()} marka isleniyor (delay: {$delay}ms)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($brands->count());
        $bar->start();

        foreach ($brands as $brand) {
            $this->processBrand($brand);
            $bar->advance();
            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->printSummary();

        // Clear brand cache
        \Illuminate\Support\Facades\Cache::forget('brands.active');
        \Illuminate\Support\Facades\Cache::forget('brands.featured');

        return self::SUCCESS;
    }

    private function processBrand(Brand $brand): void
    {
        $searchQuery = $brand->name . ' brand logo transparent';
        $imageUrl = $this->searchGoogleImages($searchQuery, $brand->name);

        if (! $imageUrl) {
            // Fallback: try without "transparent"
            $imageUrl = $this->searchGoogleImages($brand->name . ' logo png', $brand->name);
        }

        if (! $imageUrl) {
            $this->notFound++;
            $this->missingBrands[] = $brand->name;
            return;
        }

        if ($this->option('dry-run')) {
            $this->found++;
            $shortUrl = Str::limit($imageUrl, 80);
            $this->foundBrands[] = "{$brand->name} → {$shortUrl}";
            return;
        }

        $saved = $this->downloadAndSave($brand, $imageUrl);

        if ($saved) {
            $this->found++;
            $this->foundBrands[] = $brand->name;
        } else {
            $this->failed++;
            $this->missingBrands[] = $brand->name . ' (indirme hatasi)';
        }
    }

    /**
     * Google Images arama sonuclarindan ilk uygun gorseli bul
     */
    private function searchGoogleImages(string $query, string $brandName): ?string
    {
        try {
            $url = 'https://www.google.com/search?' . http_build_query([
                'q' => $query,
                'tbm' => 'isch',
                'ijn' => '0',
            ]);

            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                ])
                ->get($url);

            if ($response->failed()) {
                return null;
            }

            $html = $response->body();

            // Google Images stores image URLs in JSON data within the page
            // Look for high-quality image URLs in the response
            $imageUrls = $this->extractImageUrls($html);

            if (empty($imageUrls)) {
                return null;
            }

            // Filter: prefer PNG, reasonable size, skip tiny icons and huge files
            foreach ($imageUrls as $imgUrl) {
                // Skip data URIs, google internal, and tiny thumbnails
                if (str_starts_with($imgUrl, 'data:')) continue;
                if (str_contains($imgUrl, 'google.com')) continue;
                if (str_contains($imgUrl, 'gstatic.com')) continue;
                if (str_contains($imgUrl, 'googleapis.com')) continue;
                if (str_contains($imgUrl, 'wikipedia.org/wiki/')) continue;

                // Prefer actual image files
                if (preg_match('/\.(png|jpg|jpeg|webp|svg)/i', $imgUrl)) {
                    return $imgUrl;
                }
            }

            // If no direct image match, return first non-google URL
            return $imageUrls[0] ?? null;

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Google Images HTML'inden gorsel URL'lerini cikar
     */
    private function extractImageUrls(string $html): array
    {
        $urls = [];

        // Method 1: Extract from AF_initDataCallback JSON blocks
        // Google embeds full-size image URLs in these data blocks
        if (preg_match_all('/\["(https?:\/\/[^"]+\.(?:png|jpg|jpeg|webp)(?:\?[^"]*)?)",\s*\d+,\s*\d+\]/', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $decoded = str_replace('\u003d', '=', $url);
                $decoded = str_replace('\u0026', '&', $decoded);
                if (! str_contains($decoded, 'google.com') && ! str_contains($decoded, 'gstatic.com')) {
                    $urls[] = $decoded;
                }
            }
        }

        // Method 2: Look for image URLs in data-src or src attributes
        if (empty($urls) && preg_match_all('/(?:data-src|src)="(https?:\/\/[^"]+\.(?:png|jpg|jpeg|webp)[^"]*)"/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                if (! str_contains($url, 'google.com') && ! str_contains($url, 'gstatic.com')) {
                    $urls[] = html_entity_decode($url);
                }
            }
        }

        // Method 3: Extract any https image URL from the page
        if (empty($urls) && preg_match_all('/"(https?:\/\/[^"]*\.(?:png|jpg|jpeg)(?:\?[^"]*)?)"/i', $html, $matches)) {
            foreach ($matches[1] as $url) {
                $decoded = str_replace(['\\u003d', '\\u0026', '\\/', '\\x3d', '\\x26'], ['=', '&', '/', '=', '&'], $url);
                if (! str_contains($decoded, 'google.com')
                    && ! str_contains($decoded, 'gstatic.com')
                    && ! str_contains($decoded, 'googleapis.com')
                    && strlen($decoded) < 500
                ) {
                    $urls[] = $decoded;
                }
            }
        }

        // Deduplicate
        return array_values(array_unique($urls));
    }

    private function downloadAndSave(Brand $brand, string $imageUrl): bool
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                    'Accept' => 'image/*,*/*;q=0.8',
                    'Referer' => 'https://www.google.com/',
                ])
                ->get($imageUrl);

            if ($response->failed()) {
                return false;
            }

            $body = $response->body();

            // Minimum 2KB — skip tiny/placeholder images
            if (strlen($body) < 2000) {
                return false;
            }

            // Maximum 2MB — skip absurdly large files
            if (strlen($body) > 2 * 1024 * 1024) {
                return false;
            }

            // Determine extension from content type
            $contentType = $response->header('Content-Type') ?? '';
            $ext = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'svg') => 'svg',
                default => 'png',
            };

            $filename = "brands/" . Str::slug($brand->name) . ".{$ext}";

            Storage::disk('public')->put($filename, $body);

            $brand->update([
                'logo_url' => "/storage/{$filename}",
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function printSummary(): void
    {
        $this->info("=== Sonuc ===");
        $this->info("Bulundu & Kaydedildi: {$this->found}");
        $this->warn("Bulunamadi:           {$this->notFound}");

        if ($this->failed > 0) {
            $this->error("Hatali:               {$this->failed}");
        }

        if (! empty($this->foundBrands)) {
            $this->newLine();
            $this->info("Basarili markalar:");
            foreach ($this->foundBrands as $info) {
                $this->line("  + {$info}");
            }
        }

        if (! empty($this->missingBrands)) {
            $this->newLine();
            $this->warn("Logosu bulunamayan markalar:");
            foreach ($this->missingBrands as $name) {
                $this->line("  - {$name}");
            }
        }
    }
}
