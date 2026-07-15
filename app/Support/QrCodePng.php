<?php

namespace App\Support;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class QrCodePng
{
    /**
     * Generate a PNG QR code as a raw binary string using GD.
     *
     * BaconQrCode ships only SVG/Imagick renderers; Imagick isn't installed here and email
     * clients (Gmail) don't render inline SVG, so we rasterize the QR matrix ourselves with GD.
     */
    public static function generate(string $text, int $targetSize = 320, int $margin = 4): string
    {
        $matrix = Encoder::encode($text, ErrorCorrectionLevel::M())->getMatrix();
        $count = $matrix->getWidth();

        $moduleSize = max(1, (int) floor($targetSize / ($count + $margin * 2)));
        $imageSize = ($count + $margin * 2) * $moduleSize;

        $image = imagecreatetruecolor($imageSize, $imageSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $dark = imagecolorallocate($image, 17, 24, 39);
        imagefilledrectangle($image, 0, 0, $imageSize, $imageSize, $white);

        for ($y = 0; $y < $count; $y++) {
            for ($x = 0; $x < $count; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x0 = ($x + $margin) * $moduleSize;
                    $y0 = ($y + $margin) * $moduleSize;
                    imagefilledrectangle($image, $x0, $y0, $x0 + $moduleSize - 1, $y0 + $moduleSize - 1, $dark);
                }
            }
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
