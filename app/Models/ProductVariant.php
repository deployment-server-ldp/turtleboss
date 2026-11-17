<?php
namespace App\Models;

class ProductVariant extends Model
{
    protected $casts = ['price' => 'float', 'sale_price' => 'float', 'is_active' => 'boolean'];
    public function product() { return $this->belongsTo(Product::class); }
    public function values() { return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values', 'variant_id', 'attribute_value_id')->with('attribute'); }
    public function images() { return $this->hasMany(ProductImage::class, 'variant_id')->orderBy('sort_order'); }

    public function regular_price_for(Product $p): float { return (float) ($this->price ?? $p->price); }
    public function final_price_for(Product $p): float
    {
        $regular = $this->regular_price_for($p);
        $sale = $this->price !== null ? $this->sale_price : ($this->sale_price ?? $p->sale_price);
        return $sale !== null && $sale > 0 && $sale < $regular ? (float) $sale : $regular;
    }
    public function getLabelAttribute(): string
    {
        return $this->values->sortBy(fn ($v) => $v->attribute->sort_order)->map(fn ($v) => $v->attribute->name . ': ' . $v->value)->implode(', ');
    }
}
