<?php

namespace App\Services;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;

class CloudinaryService
{
    public function __construct()
    {
        $url = config('cloudinary.url');

        if ($url) {
            Configuration::instance($url);
        } else {
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => config('cloudinary.cloud_name'),
                    'api_key' => config('cloudinary.api_key'),
                    'api_secret' => config('cloudinary.api_secret'),
                ],
                'url' => ['secure' => true],
            ]);
        }
    }

    public function ping(): mixed
    {
        return (new AdminApi())->ping();
    }

    /**
     * Sube imagen a Cloudinary.
     * $file puede ser path local, data URI base64 o UploadedFile.
     */
    public function upload(mixed $file, array $options = []): mixed
    {
        $defaults = [
            'folder' => config('cloudinary.folder'),
        ];

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $file = $file->getRealPath();
        }

        return (new UploadApi())->upload($file, array_merge($defaults, $options));
    }

    public function destroy(string $publicId, array $options = []): mixed
    {
        return (new UploadApi())->destroy($publicId, $options);
    }

    public function deleteResources(array $publicIds): mixed
    {
        return (new AdminApi())->deleteResources($publicIds);
    }
}
