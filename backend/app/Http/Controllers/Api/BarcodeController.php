<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class BarcodeController extends Controller
{
    /**
     * Decode a barcode from an uploaded image.
     *
     * Pipeline:
     * 1. Normalize (HEIC -> JPEG via heif-convert)
     * 2. Enhance (ImageMagick: resize, grayscale, contrast)
     * 3. zbarimg -> exact barcode line decode
     * 4. Tesseract OCR fallback -> extract printed digits
     */
    public function decode(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|max:15360',
        ]);

        $uploaded = $request->file('image');
        $mime = $uploaded->getMimeType();
        $ext = strtolower($uploaded->getClientOriginalExtension() ?: $uploaded->extension());
        $trace = [
            'mime' => $mime,
            'ext' => $ext,
            'size_kb' => (int) round($uploaded->getSize() / 1024),
        ];

        $workDir = sys_get_temp_dir().'/barcode-'.Str::random(8);
        @mkdir($workDir, 0700, true);

        try {
            $inputPath = $workDir.'/input.'.($ext ?: 'bin');
            copy($uploaded->getRealPath(), $inputPath);

            $jpegPath = $this->normalizeToJpeg($inputPath, $workDir, $mime, $ext, $trace);

            $enhancedPath = $this->enhanceForDecode($jpegPath, $workDir, $trace);

            foreach ([$jpegPath, $enhancedPath] as $candidate) {
                if ($candidate === null) {
                    continue;
                }
                $zbar = $this->runZbar($candidate, $trace);
                if ($zbar !== null) {
                    return response()->json([
                        'barcode' => $zbar,
                        'source' => 'zbar',
                    ]);
                }
            }

            foreach ([$enhancedPath, $jpegPath] as $candidate) {
                if ($candidate === null) {
                    continue;
                }
                $ocr = $this->runOcr($candidate, $trace);
                if ($ocr !== null) {
                    return response()->json([
                        'barcode' => $ocr,
                        'source' => 'ocr',
                    ]);
                }
            }

            return response()->json([
                'barcode' => null,
                'source' => null,
                'message' => 'Görselden barkod ya da sayı okunamadı.',
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Barcode decode failed', [
                'error' => $e->getMessage(),
                'trace' => $trace,
            ]);

            return response()->json([
                'barcode' => null,
                'message' => 'İşleme sırasında bir hata oluştu.',
            ], 500);
        } finally {
            $this->cleanDir($workDir);
        }
    }

    private function normalizeToJpeg(string $inputPath, string $workDir, ?string $mime, string $ext, array &$trace): string
    {
        $isHeic = in_array($ext, ['heic', 'heif'], true)
            || in_array($mime, ['image/heic', 'image/heif'], true);

        if ($isHeic) {
            $outPath = $workDir.'/normalized.jpg';
            if ($this->binaryExists('heif-convert')) {
                $result = Process::timeout(10)->run(['heif-convert', $inputPath, $outPath]);
                if ($result->successful() && is_file($outPath)) {
                    $trace['normalized'] = 'heif-convert';

                    return $outPath;
                }
                $trace['heif_error'] = trim($result->errorOutput() ?: $result->output());
            }
        }

        if ($this->binaryExists('convert')) {
            $outPath = $workDir.'/normalized.jpg';
            $result = Process::timeout(10)->run(['convert', $inputPath, '-auto-orient', $outPath]);
            if ($result->successful() && is_file($outPath)) {
                $trace['normalized'] = 'imagemagick';

                return $outPath;
            }
        }

        $trace['normalized'] = 'none';

        return $inputPath;
    }

    private function enhanceForDecode(string $jpegPath, string $workDir, array &$trace): ?string
    {
        if (! $this->binaryExists('convert')) {
            return null;
        }

        $outPath = $workDir.'/enhanced.png';
        $result = Process::timeout(10)->run([
            'convert', $jpegPath,
            '-auto-orient',
            '-resize', '1600x1600>',
            '-colorspace', 'Gray',
            '-normalize',
            '-sharpen', '0x1',
            '-contrast-stretch', '2%x2%',
            $outPath,
        ]);

        if (! $result->successful() || ! is_file($outPath)) {
            $trace['enhance_error'] = trim($result->errorOutput() ?: $result->output());

            return null;
        }

        $trace['enhanced'] = true;

        return $outPath;
    }

    private function runZbar(string $path, array &$trace): ?string
    {
        if (! $this->binaryExists('zbarimg')) {
            return null;
        }

        $result = Process::timeout(10)->run(['zbarimg', '--quiet', '--raw', $path]);
        if (! $result->successful()) {
            $trace['zbar_error'] = trim($result->errorOutput() ?: $result->output());

            return null;
        }

        $output = trim($result->output());
        if ($output === '') {
            return null;
        }

        $lines = preg_split('/\r?\n/', $output);
        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            $candidate = trim($line);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    private function runOcr(string $path, array &$trace): ?string
    {
        if (! $this->binaryExists('tesseract')) {
            return null;
        }

        $result = Process::timeout(20)->run([
            'tesseract', $path, 'stdout',
            '--psm', '6',
            '-c', 'tessedit_char_whitelist=0123456789',
        ]);

        if (! $result->successful()) {
            $trace['ocr_error'] = trim($result->errorOutput() ?: $result->output());

            return null;
        }

        $digits = $this->extractBarcodeDigits($result->output());
        if ($digits !== null) {
            $trace['ocr_raw'] = trim($result->output());
        }

        return $digits;
    }

    private function extractBarcodeDigits(string $text): ?string
    {
        $clean = preg_replace('/[^\d\s]/', ' ', $text) ?? '';
        preg_match_all('/\d{8,14}/', $clean, $matches);
        if (empty($matches[0])) {
            return null;
        }

        usort($matches[0], fn ($a, $b) => strlen($b) <=> strlen($a));

        return $matches[0][0];
    }

    private function binaryExists(string $name): bool
    {
        $result = Process::run(['which', $name]);

        return $result->successful() && trim($result->output()) !== '';
    }

    private function cleanDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
