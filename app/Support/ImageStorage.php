<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image as ProcessedImage;
use Illuminate\Support\Facades\Image;
use RuntimeException;
use Throwable;

/**
 * Stores uploaded images through Laravel's image pipeline, so multi-megabyte
 * phone photos land on disk right-sized and re-encoded instead of verbatim.
 *
 * Every helper degrades to an unprocessed store when the image cannot be
 * decoded, so a bad upload is never lost to an optimization failure.
 */
final class ImageStorage
{
    /** Widest edge kept for gallery and cover photography. */
    public const PHOTO_MAX_WIDTH = 1920;

    /** Widest edge kept for payment receipts, which must stay readable. */
    public const RECEIPT_MAX_WIDTH = 1600;

    /** Widest edge kept for payment QR codes. */
    public const QR_MAX_WIDTH = 800;

    /**
     * Store a venue or court photo as a right-sized WebP.
     */
    public static function photo(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store(
            $file,
            $directory,
            $disk,
            fn (ProcessedImage $image): ProcessedImage => $image
                ->orient()
                ->scale(width: self::PHOTO_MAX_WIDTH)
                ->toWebp()
                ->quality(80),
        );
    }

    /**
     * Store a payment receipt, favouring legibility over file size.
     */
    public static function receipt(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store(
            $file,
            $directory,
            $disk,
            fn (ProcessedImage $image): ProcessedImage => $image
                ->orient()
                ->scale(width: self::RECEIPT_MAX_WIDTH)
                ->toWebp()
                ->quality(85),
        );
    }

    /**
     * Store a payment QR code losslessly — lossy artefacts break scanning.
     */
    public static function qrCode(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        return self::store(
            $file,
            $directory,
            $disk,
            fn (ProcessedImage $image): ProcessedImage => $image
                ->orient()
                ->scale(width: self::QR_MAX_WIDTH)
                ->toPng(),
        );
    }

    /**
     * Run the upload through the given pipeline, falling back to a plain store.
     *
     * @param  Closure(ProcessedImage): ProcessedImage  $pipeline
     */
    private static function store(UploadedFile $file, string $directory, string $disk, Closure $pipeline): string
    {
        try {
            $path = $pipeline(Image::fromUpload($file))->storePublicly(path: $directory, disk: $disk);

            if (is_string($path)) {
                return $path;
            }
        } catch (Throwable) {
            // Fall through to storing the original bytes.
        }

        $storedPath = $file->store($directory, $disk);

        if ($storedPath === false) {
            throw new RuntimeException("Unable to store the uploaded image in [{$directory}].");
        }

        return $storedPath;
    }
}
