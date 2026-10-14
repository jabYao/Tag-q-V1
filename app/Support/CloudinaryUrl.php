<?php

namespace App\Support;

/**
 * Small render-time helper that asks Cloudinary for a WebP image with
 * automatic quality.
 *
 * The transformation is applied only to the URL rendered in an `<img src>`:
 * the value stored in the database (and used to retain or delete the original
 * asset) is never touched. Non-Cloudinary URLs and URLs that already carry the
 * `f_webp`/`q_auto` components are returned unchanged, so the helper is safe to
 * call more than once.
 */
final class CloudinaryUrl
{
    private const CLOUDINARY_HOST = 'res.cloudinary.com';

    private const IMAGE_UPLOAD_SEGMENT = '/image/upload/';

    private const FORMAT_COMPONENT = 'f_webp';

    private const QUALITY_COMPONENT = 'q_auto';

    /**
     * Transform a Cloudinary image delivery URL to WebP + automatic quality.
     *
     * Non-Cloudinary URLs and any value that is not a Cloudinary image
     * delivery URL are returned unchanged.
     */
    public static function webp(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        if (parse_url($url, PHP_URL_HOST) !== self::CLOUDINARY_HOST) {
            return $url;
        }

        $offset = strpos($url, self::IMAGE_UPLOAD_SEGMENT);
        if ($offset === false) {
            return $url;
        }

        $offset += strlen(self::IMAGE_UPLOAD_SEGMENT);

        $after = substr($url, $offset);
        $slash = strpos($after, '/');
        $head = $slash === false ? $after : substr($after, 0, $slash);
        $second = self::firstSegment($slash === false ? '' : substr($after, $slash + 1));

        // A transformation chain is a leading component that is followed by a
        // version (`v123`). Everything else — a version or the public id
        // itself — means there is no transformation yet.
        $hasTransformations = $head !== ''
            && preg_match('/^v\d+$/', $head) !== 1
            && preg_match('/^v\d+$/', $second) === 1;

        if (! $hasTransformations) {
            return substr_replace(
                $url,
                self::FORMAT_COMPONENT.','.self::QUALITY_COMPONENT.'/',
                $offset,
                0,
            );
        }

        $components = array_values(array_filter(
            explode(',', $head),
            fn (string $component): bool => $component !== '',
        ));

        if (in_array(self::FORMAT_COMPONENT, $components, true)
            && in_array(self::QUALITY_COMPONENT, $components, true)) {
            return $url;
        }

        if (! in_array(self::FORMAT_COMPONENT, $components, true)) {
            $components[] = self::FORMAT_COMPONENT;
        }

        if (! in_array(self::QUALITY_COMPONENT, $components, true)) {
            $components[] = self::QUALITY_COMPONENT;
        }

        return substr_replace($url, implode(',', $components), $offset, strlen($head));
    }

    private static function firstSegment(string $value): string
    {
        $slash = strpos($value, '/');

        return $slash === false ? $value : substr($value, 0, $slash);
    }
}
