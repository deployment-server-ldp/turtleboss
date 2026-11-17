<div class="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
    <span class="text-3xl font-bold" :class="onSale ? 'text-sale' : 'text-navy'" x-text="(!variant && cfg.hasVariants && cfg.variants.length > 1 ? 'From ' : '') + money(price)">{{ money($p->final_price) }}</span>
    <span class="text-lg text-muted line-through" x-show="onSale" x-text="money(regular)"></span>
    <span class="badge bg-red-50 text-sale" x-show="onSale" x-text="'Save ' + discount + '%'"></span>
</div>
<p class="mt-3 flex items-center gap-2 text-sm font-medium" :class="variant === false || (stock !== null && stock <= 0) ? 'text-red-600' : (stock !== null && stock < 10 ? 'text-amber-600' : 'text-green-700')">
    <span class="h-2 w-2 rounded-full bg-current"></span><span x-text="stockText">{{ $p->in_stock ? 'In stock' : 'Out of stock' }}</span>
</p>
