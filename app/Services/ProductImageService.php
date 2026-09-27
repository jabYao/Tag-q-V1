<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ProductImageService
{
    public function __construct(private CloudinaryService $cloudinary) {}

    /**
     * Sube múltiples imágenes y devuelve array de secure_urls.
     * @param UploadedFile[] $files
     * @return string[]
     */
    public function uploadMany(array $files): array
    {
        $urls = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $result = $this->cloudinary->upload($file->getRealPath(), [
                'folder' => config('cloudinary.folder', 'tag-q/producto'),
            ]);
            // Cloudinary v2 devuelve ApiResponse (ArrayAccess) o array
            $url = is_array($result) ? ($result['secure_url'] ?? null) : ($result['secure_url'] ?? null);
            if ($url) {
                $urls[] = $url;
            }
        }
        return $urls;
    }

    /**
     * Elimina imágenes de Cloudinary a partir de sus URLs.
     * @param string[] $urls
     */
    public function deleteByUrls(array $urls): void
    {
        foreach ($urls as $url) {
            $publicId = $this->extractPublicId($url);
            if ($publicId) {
                try {
                    $this->cloudinary->destroy($publicId);
                } catch (\Throwable $e) {
                    // No romper el flujo si Cloudinary falla — log silencioso en v1
                    logger()->warning('Cloudinary destroy falló', ['url' => $url, 'error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Extrae public_id desde una secure_url de Cloudinary.
     * Ej: https://res.cloudinary.com/dg6iut6sl/image/upload/v123/tag-q/producto/abc123.jpg -> tag-q/producto/abc123
     */
    public function extractPublicId(string $url): ?string
    {
        // Busca el segmento después de /upload/
        if (preg_match('#/upload/(?:v\d+/)?(.+)\.[a-zA-Z0-9]+$#', $url, $m)) {
            return $m[1];
        }
        return null;
    }
}
