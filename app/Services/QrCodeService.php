<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\LabelAlignment;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Generate a QR code for a registration URL
     *
     * @param string $url The registration URL to encode
     * @param array $options Configuration options
     * @return array Contains 'data_uri', 'svg', 'png_path', 'svg_path'
     */
    public function generateRegistrationQrCode(string $url, array $options = []): array
    {
        // Default options
        $options = array_merge([
            'size' => 300,
            'margin' => 10,
            'error_correction' => ErrorCorrectionLevel::Medium,
            'encoding' => 'UTF-8',
            'round_block_size' => RoundBlockSizeMode::Margin,
            'label' => null,
            'logo_path' => null,
            'foreground_color' => [0, 0, 0], // Black
            'background_color' => [255, 255, 255], // White
        ], $options);

        // Create QR code
        $qrCode = new QrCode(
            data: $url,
            encoding: new Encoding($options['encoding']),
            errorCorrectionLevel: $options['error_correction'],
            size: $options['size'],
            margin: $options['margin'],
            roundBlockSizeMode: $options['round_block_size'],
            foregroundColor: new Color($options['foreground_color'][0], $options['foreground_color'][1], $options['foreground_color'][2]),
            backgroundColor: new Color($options['background_color'][0], $options['background_color'][1], $options['background_color'][2])
        );

        // Create label if provided
        $label = null;
        if (!empty($options['label'])) {
            $label = new Label(
                text: $options['label'],
                alignment: LabelAlignment::Center
            );
        }

        // Create logo if provided
        $logo = null;
        if (!empty($options['logo_path']) && file_exists($options['logo_path'])) {
            $logo = new Logo(
                path: $options['logo_path'],
                resizeToWidth: 50,
                punchoutBackground: true
            );
        }

        // Generate PNG
        $pngWriter = new PngWriter();
        $pngResult = $pngWriter->write($qrCode, $logo, $label);
        
        // Generate SVG
        $svgWriter = new SvgWriter();
        $svgResult = $svgWriter->write($qrCode, $logo, $label);

        // Generate unique filename
        $filename = 'qr-codes/' . Str::uuid();
        
        // Save files to storage
        $pngPath = $filename . '.png';
        $svgPath = $filename . '.svg';
        
        Storage::disk('public')->put($pngPath, $pngResult->getString());
        Storage::disk('public')->put($svgPath, $svgResult->getString());

        return [
            'data_uri' => $pngResult->getDataUri(),
            'svg' => $svgResult->getString(),
            'png_path' => Storage::disk('public')->url($pngPath),
            'svg_path' => Storage::disk('public')->url($svgPath),
            'local_png_path' => storage_path('app/public/' . $pngPath),
            'local_svg_path' => storage_path('app/public/' . $svgPath),
            'size' => $options['size'],
            'url' => $url,
        ];
    }

    /**
     * Generate QR code with gym branding
     *
     * @param string $url The registration URL
     * @param string $gymName The gym name for the label
     * @param string|null $logoPath Path to gym logo
     * @param array $brandColors Brand colors [foreground, background]
     * @return array
     */
    public function generateBrandedQrCode(
        string $url, 
        string $gymName, 
        ?string $logoPath = null, 
        array $brandColors = []
    ): array {
        $options = [
            'size' => 400,
            'margin' => 20,
            'label' => "Scan to Register at {$gymName}",
            'logo_path' => $logoPath,
        ];

        // Apply brand colors if provided
        if (!empty($brandColors['foreground'])) {
            $options['foreground_color'] = $this->hexToRgb($brandColors['foreground']);
        }
        if (!empty($brandColors['background'])) {
            $options['background_color'] = $this->hexToRgb($brandColors['background']);
        }

        return $this->generateRegistrationQrCode($url, $options);
    }

    /**
     * Generate multiple QR code sizes for different use cases
     *
     * @param string $url The registration URL
     * @param string $gymName The gym name
     * @param string|null $logoPath Path to gym logo
     * @return array Array of QR codes in different sizes
     */
    public function generateMultipleSizes(string $url, string $gymName, ?string $logoPath = null): array
    {
        $sizes = [
            'small' => ['size' => 200, 'margin' => 10, 'label' => null],
            'medium' => ['size' => 300, 'margin' => 15, 'label' => "Register at {$gymName}"],
            'large' => ['size' => 500, 'margin' => 25, 'label' => "Scan to Register at {$gymName}"],
            'poster' => ['size' => 800, 'margin' => 40, 'label' => "Scan to Register at {$gymName}"],
        ];

        $results = [];
        foreach ($sizes as $sizeName => $options) {
            if ($logoPath) {
                $options['logo_path'] = $logoPath;
            }
            $results[$sizeName] = $this->generateRegistrationQrCode($url, $options);
        }

        return $results;
    }

    /**
     * Clean up old QR code files
     *
     * @param int $olderThanHours Delete files older than this many hours
     * @return int Number of files deleted
     */
    public function cleanupOldQrCodes(int $olderThanHours = 24): int
    {
        $files = Storage::disk('public')->files('qr-codes');
        $deleted = 0;
        $cutoffTime = now()->subHours($olderThanHours);

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $cutoffTime->timestamp) {
                Storage::disk('public')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Convert hex color to RGB array
     *
     * @param string $hex Hex color (e.g., '#FF0000' or 'FF0000')
     * @return array RGB array [r, g, b]
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Get QR code analytics data (placeholder for future implementation)
     *
     * @param string $qrCodeId QR code identifier
     * @return array Analytics data
     */
    public function getQrCodeAnalytics(string $qrCodeId): array
    {
        // Placeholder for future analytics implementation
        return [
            'scans' => 0,
            'unique_scans' => 0,
            'last_scanned' => null,
            'registrations_completed' => 0,
        ];
    }
}
