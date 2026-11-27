<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Validates an uploaded image by content (magic bytes), size and dimensions, then re-encodes it
 * to a fresh JPEG with GD. Re-encoding drops EXIF/GPS metadata and any appended payload.
 * The result is written to the private (non-public) disk.
 */
class ImageSanitiser
{
    public function store(UploadedFile $file, string $dir = 'listing-photos'): array
    {
        $maxBytes = config('freshroute.upload.max_kb') * 1024;
        $path = $file->getRealPath();

        if (! $file->isValid() || $path === false) {
            $this->fail(__('ui.err_upload'));
        }
        if (filesize($path) > $maxBytes) {
            $this->fail(__('ui.err_upload_size', ['kb' => config('freshroute.upload.max_kb')]));
        }

        $raw = file_get_contents($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($raw);
        if (! in_array($mime, config('freshroute.upload.mimes'), true)) {
            $this->fail(__('ui.err_upload_type'));
        }

        $info = @getimagesizefromstring($raw);
        if ($info === false || $info[0] < 16 || $info[1] < 16 || $info[0] > 8000 || $info[1] > 8000) {
            $this->fail(__('ui.err_upload_type'));
        }

        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            $this->fail(__('ui.err_upload_type'));
        }

        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $img = $this->orient($img, (int) ($exif['Orientation'] ?? 1));
        }

        $max = config('freshroute.upload.max_px');
        $w = imagesx($img);
        $h = imagesy($img);
        if (max($w, $h) > $max) {
            $scale = $max / max($w, $h);
            $img = imagescale($img, (int) round($w * $scale), (int) round($h * $scale)) ?: $img;
        }

        // Flatten onto white so PNG/WebP transparency survives JPEG encoding.
        $flat = imagecreatetruecolor(imagesx($img), imagesy($img));
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));

        ob_start();
        imagejpeg($flat, null, 82);
        $jpeg = (string) ob_get_clean();

        $name = $dir.'/'.Str::uuid().'.jpg';
        Storage::disk('local')->put($name, $jpeg);

        return ['path' => $name, 'mime' => 'image/jpeg', 'bytes' => strlen($jpeg), 'sha256' => hash('sha256', $jpeg)];
    }

    private function orient(\GdImage $img, int $o): \GdImage
    {
        return match ($o) {
            3 => imagerotate($img, 180, 0) ?: $img,
            6 => imagerotate($img, -90, 0) ?: $img,
            8 => imagerotate($img, 90, 0) ?: $img,
            default => $img,
        };
    }

    private function fail(string $msg): never
    {
        throw ValidationException::withMessages(['photo' => $msg]);
    }
}
