<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    public function index(Request $r)
    {
        $q = Media::latest();
        if ($t = trim((string) $r->q)) $q->where(fn ($w) => $w->where('title', 'like', "%$t%")->orWhere('filename', 'like', "%$t%")->orWhere('alt', 'like', "%$t%"));
        $items = $q->paginate(36)->withQueryString();
        $used = $this->usedPaths();
        return view('admin.media', ['items' => $items, 'used' => $used]);
    }

    /** Every storage path referenced by content, so "unused" can be computed safely. */
    private function usedPaths(): array
    {
        $paths = collect();
        foreach ([['product_images', 'path'], ['categories', 'image'], ['sliders', 'image_desktop'], ['sliders', 'image_mobile'], ['pages', 'banner'], ['blog_posts', 'featured_image'], ['seo_metadata', 'og_image'], ['reviews', 'image']] as [$t, $c]) {
            $paths = $paths->merge(DB::table($t)->whereNotNull($c)->pluck($c));
        }
        foreach (['logo', 'favicon', 'seo_image'] as $k) if ($v = setting($k)) $paths->push($v);
        foreach (DB::table('homepage_sections')->pluck('config') as $cfg) { preg_match_all('#"image":"([^"]+)"#', (string) $cfg, $m); $paths = $paths->merge(array_map('stripslashes', $m[1])); }
        return $paths->flip()->all();
    }

    public function store(Request $r, ImageService $images)
    {
        $r->validate(['files' => 'required|array|max:20', 'files.*' => 'file|mimes:jpg,jpeg,png,webp,svg|max:8192']);
        $n = 0; $errs = [];
        foreach ($r->file('files') as $f) {
            try { $images->store($f, 'media'); $n++; } catch (\Illuminate\Validation\ValidationException $e) { $errs[] = $f->getClientOriginalName() . ': ' . $e->validator->errors()->first(); }
        }
        return back()->with('status', "$n file(s) uploaded.")->withErrors($errs ? ['upload' => implode(' ', $errs)] : []);
    }

    public function update(Request $r, Media $medium, ImageService $images)
    {
        $d = $r->validate(['title' => 'nullable|max:160', 'alt' => 'nullable|max:200', 'replace' => 'nullable|file|mimes:jpg,jpeg,png,webp,svg|max:8192']);
        $medium->update(['title' => $d['title'] ?? null, 'alt' => $d['alt'] ?? null]);
        if ($r->hasFile('replace')) {
            // Replace the file but keep every existing reference working by rewriting stored paths.
            $old = $medium->path;
            $new = $images->store($r->file('replace'), 'media', $medium->title, $medium->alt, false);
            foreach ([['product_images', 'path'], ['categories', 'image'], ['sliders', 'image_desktop'], ['sliders', 'image_mobile'], ['pages', 'banner'], ['blog_posts', 'featured_image'], ['seo_metadata', 'og_image']] as [$t, $c]) DB::table($t)->where($c, $old)->update([$c => $new]);
            foreach (['logo', 'favicon', 'seo_image'] as $k) if (setting($k) === $old) \App\Models\Setting::put($k, $new);
            $info = @getimagesize(storage_path('app/public/' . $new));
            $medium->update(['path' => $new, 'filename' => $r->file('replace')->getClientOriginalName(), 'width' => $info[0] ?? null, 'height' => $info[1] ?? null, 'size' => filesize(storage_path('app/public/' . $new))]);
            $images->delete($old);
        }
        return back()->with('status', 'Media updated.');
    }

    public function destroy(Media $medium, ImageService $images)
    {
        if (array_key_exists($medium->path, $this->usedPaths())) return back()->withErrors(['delete' => 'This image is in use. Replace it instead of deleting.']);
        $images->delete($medium->path);
        $medium->delete();
        return back()->with('status', 'Media deleted.');
    }
}
