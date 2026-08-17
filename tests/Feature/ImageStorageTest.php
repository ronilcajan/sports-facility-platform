<?php

use App\Support\ImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

test('an oversized photo is scaled down and re-encoded as webp', function (): void {
    $path = ImageStorage::photo(UploadedFile::fake()->image('huge.jpg', 4000, 3000), 'courts');

    expect($path)->toEndWith('.webp');

    $stored = Storage::disk('public')->path($path);
    [$width, $height] = getimagesize($stored);

    expect($width)->toBe(ImageStorage::PHOTO_MAX_WIDTH)
        ->and($height)->toBe(1440);
});

test('a photo smaller than the ceiling is never upscaled', function (): void {
    $path = ImageStorage::photo(UploadedFile::fake()->image('small.jpg', 640, 480), 'courts');

    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    expect($width)->toBe(640)
        ->and($height)->toBe(480);
});

test('a qr code is stored losslessly as png', function (): void {
    $path = ImageStorage::qrCode(UploadedFile::fake()->image('gcash.png', 1200, 1200), 'venue-qr');

    expect($path)->toEndWith('.png');

    [$width] = getimagesize(Storage::disk('public')->path($path));

    expect($width)->toBe(ImageStorage::QR_MAX_WIDTH);
});

test('a receipt is capped at the receipt width', function (): void {
    $path = ImageStorage::receipt(UploadedFile::fake()->image('receipt.jpg', 3000, 3000), 'receipts');

    [$width] = getimagesize(Storage::disk('public')->path($path));

    expect($width)->toBe(ImageStorage::RECEIPT_MAX_WIDTH);
});

test('an undecodable upload still gets stored rather than lost', function (): void {
    $path = ImageStorage::photo(UploadedFile::fake()->create('not-really.jpg', 10, 'image/jpeg'), 'courts');

    Storage::disk('public')->assertExists($path);
});
