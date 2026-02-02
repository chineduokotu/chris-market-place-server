<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary($this->configuration());
    }

    public function uploadServiceImage(UploadedFile $file): array
    {
        $result = $this->cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => config('cloudinary.folder', 'services'),
            'resource_type' => 'image',
        ]);

        $url = $result['secure_url'] ?? $result['url'] ?? null;
        $publicId = $result['public_id'] ?? null;

        if (!$url || !$publicId) {
            throw new RuntimeException('Cloudinary upload failed.');
        }

        return [
            'url' => $url,
            'public_id' => $publicId,
        ];
    }

    public function deleteImage(?string $publicId): void
    {
        if (!$publicId) {
            return;
        }

        $this->cloudinary->uploadApi()->destroy($publicId, [
            'resource_type' => 'image',
        ]);
    }

    private function configuration(): Configuration
    {
        return Configuration::instance([
            'cloud' => [
                'cloud_name' => config('cloudinary.cloud_name'),
                'api_key' => config('cloudinary.api_key'),
                'api_secret' => config('cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => (bool) config('cloudinary.secure', true),
            ],
        ]);
    }
}
