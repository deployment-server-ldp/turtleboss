<?php
namespace App\Models;

use App\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasSeo, SoftDeletes;
    protected $casts = [
        'price' => 'float', 'sale_price' => 'float', 'cost_price' => 'float',
        'is_active' => 'boolean', 'is_featured' => 'boolean', 'is_bestseller' => 'boolean',
        'is_new' => 'boolean', 'is_trending' => 'boolean', 'manage_stock' => 'boolean', 'is_demo' => 'boolean',
        'rating_avg' => 'float',
    ];

    public function category() { return $this->belongsTo(Category::class); }
    public function categories() { return $this->belongsToMany(Category::class); }
    public function images() { return $this->hasMany(ProductImage::class)->whereNull('variant_id')->orderBy('sort_order')->orderBy('id'); }
    public function allImages() { return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id'); }
    public function variants() { return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id'); }
    public function specs() { return $this->hasMany(ProductAttribute::class)->orderBy('sort_order'); }
    public function wishlistItems_() { return $this->hasMany(WishlistItem::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function approvedReviews() { return $this->hasMany(Review::class)->where('is_approved', true)->latest(); }
    public function relations_() { return $this->hasMany(ProductRelation::class); }
    public function relatedProducts(string $type = 'related')
    {
        return $this->belongsToMany(Product::class, 'product_relations', 'product_id', 'related_id')->wherePivot('type', $type);
    }

    public function scopeActive($q) { return $q->where('products.is_active', true); }
    public function scopeSearch($q, string $term)
    {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        return $q->where(function ($w) use ($like) {
            $w->where('products.name', 'like', $like)->orWhere('products.sku', 'like', $like)
              ->orWhere('products.brand', 'like', $like)
              ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', $like))
              ->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', $like));
        });
    }

    public function getUrlAttribute(): string { return route('product.show', $this->slug); }
    public function getHasVariantsAttribute(): bool { return $this->relationLoaded('variants') ? $this->variants->isNotEmpty() : $this->variants()->exists(); }

    /** Lowest price that is currently payable (considering variants). */
    public function getFinalPriceAttribute(): float
    {
        if ($this->has_variants) {
            $act = $this->variants->where('is_active', true);
            if ($act->isNotEmpty()) return (float) $act->min(fn ($v) => $v->final_price_for($this));
        }
        return $this->sale_price !== null && $this->sale_price > 0 && $this->sale_price < $this->price ? $this->sale_price : $this->price;
    }
    public function getRegularPriceAttribute(): float
    {
        if ($this->has_variants) {
            $act = $this->variants->where('is_active', true);
            if ($act->isNotEmpty()) {
                $cheapest = $act->sortBy(fn ($v) => $v->final_price_for($this))->first();
                return (float) ($cheapest->price ?? $this->price);
            }
        }
        return (float) $this->price;
    }
    public function getOnSaleAttribute(): bool { return $this->final_price < $this->regular_price; }
    public function getDiscountPercentAttribute(): int
    {
        return $this->on_sale && $this->regular_price > 0 ? (int) round((1 - $this->final_price / $this->regular_price) * 100) : 0;
    }
    public function getTotalStockAttribute(): int
    {
        if ($this->has_variants) return (int) $this->variants->where('is_active', true)->sum('stock_qty');
        return (int) $this->stock_qty;
    }
    public function getInStockAttribute(): bool
    {
        if ($this->stock_status === 'out_of_stock') return false;
        if ($this->stock_status === 'backorder' || !$this->manage_stock && !$this->has_variants) return true;
        return $this->total_stock > 0;
    }
    public function getIsLowStockAttribute(): bool
    {
        return $this->manage_stock && $this->in_stock && $this->total_stock <= $this->low_stock_threshold;
    }
    public function getImageUrlAttribute(): string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();
        return img($img?->path);
    }
    public function getImageAltAttribute(): string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : null;
        return $img?->alt ?: $this->name;
    }
    public function refreshRating(): void
    {
        $r = $this->reviews()->where('is_approved', true);
        $this->update(['rating_avg' => round((float) $r->avg('rating'), 2), 'rating_count' => $r->count()]);
    }
}
