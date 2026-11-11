<?php

namespace App\Services\Ai\Images;

use App\Services\Ai\Exceptions\AiContentGenerationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiImageStorageService
{
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Validate and securely store generated binary image data.
     *
     * @return array{
     *     path: string,
     *     file_name: string,
     *     mime_type: string,
     *     file_size_bytes: int,
     *     width: int,
     *     height: int
     * }
     *
     * @throws AiContentGenerationException
     */
    public function store(string $binaryData, int $brandId, string $baseFilename): array
    {
        $maxBytes = (int) config('ai.image_max_size_bytes', 5242880);
        $size = strlen($binaryData);

        if ($size === 0 || $size > $maxBytes) {
            throw new AiContentGenerationException(
                'invalid_image_size',
                "Generated image size ({$size} bytes) is invalid or exceeds maximum allowed size ({$maxBytes} bytes).",
            );
        }

        // Validate image magic bytes and dimensions
        $info = @getimagesizefromstring($binaryData);
        if ($info === false || ! isset($info['mime'])) {
            throw new AiContentGenerationException(
                'invalid_image_format',
                'Generated payload is not a valid image format.',
            );
        }

        $mime = strtolower((string) $info['mime']);
        if (! array_key_exists($mime, self::ALLOWED_MIMES)) {
            throw new AiContentGenerationException(
                'unsupported_image_mime',
                "Generated image format '{$mime}' is not supported. Supported: JPEG, PNG, WebP.",
            );
        }

        $width = (int) $info[0];
        $height = (int) $info[1];

        if ($width < 200 || $height < 200 || $width > 4096 || $height > 4096) {
            throw new AiContentGenerationException(
                'invalid_image_dimensions',
                "Image dimensions ({$width}x{$height}) are outside allowed boundaries (200x200 to 4096x4096).",
            );
        }

        $extension = self::ALLOWED_MIMES[$mime];
        $storedFilename = Str::uuid().".{$extension}";
        $relativePath = "featured-images/{$brandId}/{$storedFilename}";

        Storage::disk('public')->put($relativePath, $binaryData);

        return [
            'path' => $relativePath,
            'file_name' => $baseFilename,
            'mime_type' => $mime,
            'file_size_bytes' => $size,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Delete stored image file if exists.
     */
    public function delete(?string $path): void
    {
        if (! empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

