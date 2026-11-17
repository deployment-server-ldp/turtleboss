<?php
namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;

class InventoryService
{
    /** Apply a signed stock change to a product or variant and write a history row. */
    public function adjust(Product $product, ?ProductVariant $variant, int $change, string $reason, ?string $note = null): void
    {
        if ($variant) {
            $variant->increment('stock_qty', $change);
            $after = $variant->fresh()->stock_qty;
        } else {
            if (!$product->manage_stock) return;
            $product->increment('stock_qty', $change);
            $product = $product->fresh();
            $after = $product->stock_qty;
            if ($product->stock_status !== 'backorder') {
                $product->update(['stock_status' => $after > 0 ? 'in_stock' : 'out_of_stock']);
            }
        }
        InventoryLog::create([
            'product_id' => $product->id, 'variant_id' => $variant?->id, 'change' => $change,
            'qty_after' => $after, 'reason' => $reason, 'note' => $note, 'user_id' => Auth::id(),
        ]);
    }
}
