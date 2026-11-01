<?php

namespace App\Support;

/**
 * Small render-time helper that asks Cloudinary for a WebP image with
 * automatic quality.
 *
 * The transformation is applied only to the URL rendered in an `<img src>`:
 * the value stored in the database (and used to retain or delete the original
 * asset) is never touched. Non-Cloudinary URLs are returned unchanged, and
 * both helpers are idempotent: calling them again over an already transformed
 * URL does not duplicate components.
 *
 * {@see webp()} is the general-purpose variant. {@see webpCard()} adds
 * Cloudinary's automatic trim (`e_trim`) for product-card thumbnails, where the
 * white margin of the studio photo is wasted space.
 */
final class CloudinaryUrl
{
    private const CLOUDINARY_HOST = 'res.cloudinary.com';

    private const IMAGE_UPLOAD_SEGMENT = '/image/upload/';

    private const FORMAT_COMPONENT = 'f_webp';

    private const QUALITY_COMPONENT = 'q_auto';

    /**
     * Cloudinary's automatic trim: removes the uniform border around the
     * subject. Product photography is delivered as a square canvas with a wide
     * white margin, so the trim is what lets the watch fill a square card
     * thumbnail without stretching or cropping the piece.
     */
    private const TRIM_COMPONENT = 'e_trim';

    /**
     * Transform a Cloudinary image delivery URL to WebP + automatic quality.
     *
     * Non-Cloudinary URLs and any value that is not a Cloudinary image
     * delivery URL are returned unchanged.
     */
    public static function webp(?string $url): ?string
    {
        return self::transform($url, [
            self::FORMAT_COMPONENT,
            self::QUALITY_COMPONENT,
        ]);
    }

    /**
     * Same as {@see webp()} plus Cloudinary's automatic trim.
     *
     * Only for product cards: the trim discards the empty margin of the studio
     * photo so the piece fills the square thumbnail. Galleries and detail views
     * keep calling {@see webp()} because there the full canvas matters.
     */
    public static function webpCard(?string $url): ?string
    {
        return self::transform($url, [
            self::TRIM_COMPONENT,
            self::FORMAT_COMPONENT,
            self::QUALITY_COMPONENT,
        ]);
    }

    /**
     * @param  list<string>  $required  Transformation components the URL must carry.
     */
    private static function transform(?string $url, array $required): ?string
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
            return substr_replace($url, implode(',', $required).'/', $offset, 0);
        }

        $components = array_values(array_filter(
            explode(',', $head),
            fn (string $component): bool => $component !== '',
        ));

        $missing = array_values(array_filter(
            $required,
            fn (string $component): bool => ! in_array($component, $components, true),
        ));

        if ($missing === []) {
            return $url;
        }

        return substr_replace(
            $url,
            implode(',', array_merge($components, $missing)),
            $offset,
            strlen($head),
        );
    }

    private static function firstSegment(string $value): string
    {
        $slash = strpos($value, '/');

        return $slash === false ? $value : substr($value, 0, $slash);
    }
}
