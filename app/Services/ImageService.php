<?php
namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Secure image storage: verifies real MIME, strips unsafe SVG, resizes and converts to WebP when GD allows. */
class ImageService
{
    public const MAX_WIDTH = 1800;
    public const ALLOWED = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

    public function store(UploadedFile $file, string $dir = 'uploads', ?string $title = null, ?string $alt = null, bool $register = true): string
    {
        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED, true)) throw \Illuminate\Validation\ValidationException::withMessages(['file' => ['Only JPG, PNG, WebP and SVG images are allowed.']]);
        $name = date('Y/m') . '/' . bin2hex(random_bytes(8));
        $w = $h = null;

        if ($mime === 'image/svg+xml') {
            $svg = $this->sanitizeSvg((string) file_get_contents($file->getRealPath()));
            $path = "$dir/$name.svg";
            Storage::disk('public')->put($path, $svg);
            $size = strlen($svg);
        } else {
            $img = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
            if (!$img) throw \Illuminate\Validation\ValidationException::withMessages(['file' => ['The uploaded file is not a valid image.']]);
            $w = imagesx($img); $h = imagesy($img);
            if ($w > self::MAX_WIDTH) {
                $img = imagescale($img, self::MAX_WIDTH);
                $w = imagesx($img); $h = imagesy($img);
            }
            imagepalettetotruecolor($img);
            imagealphablending($img, true); imagesavealpha($img, true);
            ob_start();
            if (function_exists('imagewebp')) { imagewebp($img, null, 82); $ext = 'webp'; $mime = 'image/webp'; }
            else { $mime === 'image/png' ? imagepng($img, null, 7) : imagejpeg($img, null, 85); $ext = $mime === 'image/png' ? 'png' : 'jpg'; }
            $bin = ob_get_clean();
            imagedestroy($img);
            $path = "$dir/$name.$ext";
            Storage::disk('public')->put($path, $bin);
            $size = strlen($bin);
        }
        if ($register) {
            Media::create(['path' => $path, 'filename' => $file->getClientOriginalName(), 'mime' => $mime, 'size' => $size, 'width' => $w, 'height' => $h,
                'title' => $title ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 'alt' => $alt]);
        }
        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && !str_starts_with($path, 'http') && !str_starts_with($path, 'demo/')) Storage::disk('public')->delete($path);
    }

    /** Remove scripts, event handlers, foreignObject and external refs from SVG uploads. */
    public function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('/<\?xml.*?\?>/s', '', $svg);
        $svg = preg_replace('#<!DOCTYPE.*?>#is', '', $svg);
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($svg, LIBXML_NONET)) throw \Illuminate\Validation\ValidationException::withMessages(['file' => ['Invalid SVG file.']]);
        $xp = new \DOMXPath($dom);
        foreach (['script', 'foreignObject', 'iframe', 'embed', 'object', 'use', 'animate', 'set'] as $tag) {
            foreach (iterator_to_array($xp->query("//*[local-name()='$tag']")) as $n) $n->parentNode->removeChild($n);
        }
        foreach (iterator_to_array($xp->query('//@*')) as $attr) {
            $n = strtolower($attr->nodeName); $v = strtolower(trim($attr->nodeValue));
            if (str_starts_with($n, 'on') || str_contains($v, 'javascript:') || (in_array($n, ['href', 'xlink:href']) && !str_starts_with($v, '#'))) {
                $attr->ownerElement->removeAttributeNode($attr);
            }
        }
        return $dom->saveXML($dom->documentElement);
    }
}
