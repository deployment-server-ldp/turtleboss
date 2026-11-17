<?php
namespace App\Support;

use App\Models\Product;

/** Builds the single set of meta tags / JSON-LD a page emits (layout renders it once => no duplicates). */
class Seo
{
    public static function make(array $o = []): array
    {
        $site = setting('site_name', 'Turtle Boss');
        $title = $o['title'] ?? setting('seo_title', $site);
        $full = ($o['append_site'] ?? true) && !str_contains($title, $site) ? "$title | $site" : $title;
        $desc = $o['description'] ?? setting('seo_description', '');
        return [
            'title' => $full,
            'description' => mb_substr(trim(strip_tags($desc)), 0, 300),
            'image' => $o['image'] ?? (setting('seo_image') ? img(setting('seo_image')) : null),
            'canonical' => $o['canonical'] ?? self::cleanCanonical(),
            'noindex' => $o['noindex'] ?? false,
            'type' => $o['type'] ?? 'website',
            'og_title' => $o['og_title'] ?? $title,
            'og_description' => $o['og_description'] ?? mb_substr(trim(strip_tags($desc)), 0, 300),
            'prev' => $o['prev'] ?? null, 'next' => $o['next'] ?? null,
            'schema' => $o['schema'] ?? [],
        ];
    }

    /** Canonical = current URL without tracking/filter query strings (keeps ?page for pagination > 1). */
    public static function cleanCanonical(): string
    {
        $url = url()->current();
        $page = (int) request('page', 1);
        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    /** Merge a model's seo_metadata row over defaults. */
    public static function fromModel($model, string $defaultTitle, ?string $defaultDesc, ?string $defaultImage, array $extra = []): array
    {
        $s = $model->seo;
        return self::make(array_merge([
            'title' => $s?->title ?: $defaultTitle,
            'description' => $s?->description ?: $defaultDesc,
            'image' => $s?->og_image ? img($s->og_image) : $defaultImage,
            'canonical' => $s?->canonical_url ?: null,
            'noindex' => (bool) $s?->noindex,
            'og_title' => $s?->og_title ?: ($s?->title ?: $defaultTitle),
            'og_description' => $s?->og_description ?: ($s?->description ?: $defaultDesc),
        ], array_filter($extra, fn ($v) => $v !== null)));
    }

    public static function breadcrumbSchema(array $crumbs): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => collect($crumbs)->values()->map(fn ($c, $i) => [
            '@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url'],
        ])->all()];
    }

    public static function productSchema(Product $p, array $images): array
    {
        $offer = fn ($price, $inStock) => [
            '@type' => 'Offer', 'priceCurrency' => 'PKR', 'price' => number_format($price, 2, '.', ''), 'url' => $p->url,
            'availability' => 'https://schema.org/' . ($inStock ? 'InStock' : 'OutOfStock'),
        ];
        $data = [
            '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p->name,
            'description' => strip_tags($p->short_description ?: $p->description ?: $p->name),
            'image' => $images, 'sku' => $p->sku, 'url' => $p->url,
            'offers' => $offer($p->final_price, $p->in_stock),
        ];
        if ($p->brand) $data['brand'] = ['@type' => 'Brand', 'name' => $p->brand];
        if ($p->rating_count > 0) $data['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => $p->rating_avg, 'reviewCount' => $p->rating_count];
        return array_filter($data);
    }
}
