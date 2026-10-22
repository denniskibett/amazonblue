<?php

namespace App\Services\Kyc;

/**
 * Staff crop a KYC picture to the part that matters (owner, 22 Oct 2026): the ID card without the table around it,
 * a passport photo to its frame. The first crop keeps the untouched upload beside it as {file}.original, so a bad
 * crop can always be undone from the server; later crops start again from that original.
 */
class ImageCrop
{
    public const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * Crop the picture at $absolute to the box (x, y, width, height in the original picture's pixels).
     * Returns [width, height] of the saved picture.
     */
    public static function crop(string $absolute, int $x, int $y, int $w, int $h, int $rotate = 0): array
    {
        $original = $absolute . '.original';
        if (! is_file($original)) {
            copy($absolute, $original);
        }
        $info = @getimagesize($original);
        if (! $info || ! in_array($info['mime'], self::TYPES, true)) {
            throw new \DomainException('Only JPG, PNG or WebP pictures can be cropped.');
        }
        $src = match ($info['mime']) {
            'image/png' => imagecreatefrompng($original),
            'image/webp' => imagecreatefromwebp($original),
            default => imagecreatefromjpeg($original),
        };
        if (in_array($rotate, [90, 180, 270], true)) {
            $src = imagerotate($src, 360 - $rotate, 0);   // the screen turns clockwise, GD counter-clockwise
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $x = max(0, min($x, $sw - 1));
        $y = max(0, min($y, $sh - 1));
        $w = max(1, min($w, $sw - $x));
        $h = max(1, min($h, $sh - $y));

        $out = imagecrop($src, ['x' => $x, 'y' => $y, 'width' => $w, 'height' => $h]);
        if (! $out) {
            throw new \DomainException('The picture could not be cropped.');
        }
        // the cropped picture keeps the file name (and its format) so every link to it still works
        match ($info['mime']) {
            'image/png' => imagepng($out, $absolute, 6),
            'image/webp' => imagewebp($out, $absolute, 90),
            default => imagejpeg($out, $absolute, 92),
        };

        return [$w, $h];
    }

    /** Put the untouched upload back. */
    public static function restore(string $absolute): bool
    {
        $original = $absolute . '.original';

        return is_file($original) && copy($original, $absolute);
    }

    public static function hasOriginal(?string $absolute): bool
    {
        return $absolute && is_file($absolute . '.original');
    }
}
