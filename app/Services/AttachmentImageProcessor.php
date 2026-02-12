<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttachmentImageProcessor
{
    /**
     * @return array{path:string,mime_type:string,size_bytes:int}
     */
    public function processAndStore(UploadedFile $file): array
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The GD extension is required for attachment processing.');
        }

        $sourcePath = $file->getRealPath();
        $mimeType = $file->getMimeType();

        if ($sourcePath === false || $mimeType === null) {
            throw new RuntimeException('Invalid image upload.');
        }

        [$source, $width, $height] = $this->createImageResource($sourcePath, $mimeType);

        $targetWidth = min($width, 1600);
        $targetHeight = (int) round(($height / $width) * $targetWidth);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        [$binary, $outputMime, $extension] = $this->encodeImage($canvas, $mimeType);

        imagedestroy($source);
        imagedestroy($canvas);

        $path = sprintf('rengiat-attachments/%s.%s', Str::uuid()->toString(), $extension);
        Storage::disk('public')->put($path, $binary);

        return [
            'path' => $path,
            'mime_type' => $outputMime,
            'size_bytes' => strlen($binary),
        ];
    }

    /**
     * @return array{0:\GdImage|resource,1:int,2:int}
     */
    private function createImageResource(string $path, string $mimeType): array
    {
        $size = getimagesize($path);

        if ($size === false) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        [$width, $height] = $size;

        $resource = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw new RuntimeException('Unsupported image type.'),
        };

        if ($resource === false) {
            throw new RuntimeException('Failed to read uploaded image.');
        }

        return [$resource, $width, $height];
    }

    /**
     * @param  \GdImage|resource  $resource
     * @return array{0:string,1:string,2:string}
     */
    private function encodeImage($resource, string $sourceMimeType): array
    {
        ob_start();

        $result = match ($sourceMimeType) {
            'image/png' => imagepng($resource, null, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($resource, null, 75) : imagejpeg($resource, null, 75),
            default => imagejpeg($resource, null, 75),
        };

        $binary = ob_get_clean();

        if ($result === false || $binary === false) {
            throw new RuntimeException('Failed to encode processed image.');
        }

        if ($sourceMimeType === 'image/png') {
            return [$binary, 'image/png', 'png'];
        }

        if ($sourceMimeType === 'image/webp' && function_exists('imagewebp')) {
            return [$binary, 'image/webp', 'webp'];
        }

        return [$binary, 'image/jpeg', 'jpg'];
    }
}
