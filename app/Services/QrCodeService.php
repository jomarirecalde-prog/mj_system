<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrCodeService
{
    public function generateIdentifier(): string
    {
        $year = now('Asia/Manila')->format('Y');
        $prefix = sprintf('INV-%s-', $year);

        $latest = InventoryItem::query()
            ->where('qr_code', 'like', $prefix.'%')
            ->orderByDesc('qr_code')
            ->value('qr_code');

        $sequence = 1;

        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('INV-%s-%06d', $year, $sequence);
            $sequence++;
        } while (InventoryItem::query()->where('qr_code', $candidate)->exists());

        return $candidate;
    }

    public function ensureUnique(string $payload): string
    {
        if (! InventoryItem::query()->where('qr_code', $payload)->exists()) {
            return $payload;
        }

        $base = $payload;
        $suffix = 1;

        do {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        } while (InventoryItem::query()->where('qr_code', $candidate)->exists());

        return $candidate;
    }

    /**
     * @return string Raw image bytes (SVG or PNG depending on format)
     */
    public function generateImage(string $payload, string $format = 'svg', int $size = 300): string
    {
        $format = strtolower($format);

        // PNG requires Imagick; fall back to SVG when unavailable.
        if ($format === 'png' && ! extension_loaded('imagick')) {
            $format = 'svg';
        }

        $generator = QrCode::format($format)
            ->size($size)
            ->margin(2)
            ->errorCorrection('H');

        return (string) $generator->generate($payload);
    }

    public function storeImage(string $payload, ?string $filename = null, string $format = 'svg'): string
    {
        if ($format === 'png' && ! extension_loaded('imagick')) {
            $format = 'svg';
        }

        $filename = $filename ?: 'qr/'.md5($payload).'.'.$format;
        $contents = $this->generateImage($payload, $format);

        Storage::disk('public')->put($filename, $contents);

        return $filename;
    }

    public function download(string $payload, string $filename, string $format = 'svg'): StreamedResponse
    {
        if ($format === 'png' && ! extension_loaded('imagick')) {
            $format = 'svg';
            $filename = preg_replace('/\.png$/i', '.svg', $filename) ?: $filename.'.svg';
        }

        $contents = $this->generateImage($payload, $format);
        $mime = $format === 'png' ? 'image/png' : 'image/svg+xml';

        return response()->streamDownload(
            static function () use ($contents): void {
                echo $contents;
            },
            $filename,
            ['Content-Type' => $mime],
        );
    }
}
