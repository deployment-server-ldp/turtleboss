<?php
namespace App\Models\Concerns;

use App\Models\SeoMetadata;

trait HasSeo
{
    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }
    public function saveSeo(array $data): void
    {
        $fields = ['title','description','focus_keyword','canonical_url','og_title','og_description','og_image'];
        $clean = [];
        foreach ($fields as $f) $clean[$f] = $data[$f] ?? null;
        $clean['noindex'] = !empty($data['noindex']);
        $this->seo()->updateOrCreate([], $clean);
    }
}
