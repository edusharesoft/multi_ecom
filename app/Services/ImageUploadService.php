<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Resize and compress uploaded images before writing them to public storage. */
class ImageUploadService
{
    public const MAX_BYTES = 1048576;
    public const MAX_EDGE = 1600;

    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $storage = Storage::disk($disk);
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'image';
        $dir = trim($directory, '/');
        $path = $this->uniquePath($storage, $dir, $base);

        if (! function_exists('imagecreatefromstring')) {
            return $file->storeAs($dir, basename($path), $disk);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            return $file->storeAs($dir, basename($path), $disk);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_EDGE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 82);
        $contents = ob_get_clean() ?: '';
        $quality = 76;
        while (strlen($contents) > self::MAX_BYTES && $quality >= 50) {
            ob_start();
            imagejpeg($canvas, null, $quality);
            $contents = ob_get_clean() ?: '';
            $quality -= 8;
        }

        imagedestroy($source);
        imagedestroy($canvas);
        $storage->put($path, $contents, ['visibility' => 'public']);

        return $path;
    }

    protected function uniquePath($storage, string $directory, string $base): string
    {
        $n = 1;
        do {
            $suffix = $n === 1 ? '' : '-'.$n;
            $path = ($directory ? $directory.'/' : '').$base.$suffix.'.jpg';
            $n++;
        } while ($storage->exists($path));

        return $path;
    }
}
