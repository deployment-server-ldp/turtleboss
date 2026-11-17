<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $r)
    {
        // One row per stock-keeping unit: simple product or each variant.
        $rows = collect();
        Product::with('variants.values.attribute')->orderBy('name')->get()->each(function ($p) use (&$rows) {
            if ($p->variants->isNotEmpty()) foreach ($p->variants as $v) $rows->push(['product' => $p, 'variant' => $v, 'sku' => $v->sku, 'qty' => $v->stock_qty, 'label' => $p->name . ' — ' . $v->label, 'threshold' => $p->low_stock_threshold, 'managed' => true]);
            else $rows->push(['product' => $p, 'variant' => null, 'sku' => $p->sku, 'qty' => $p->stock_qty, 'label' => $p->name, 'threshold' => $p->low_stock_threshold, 'managed' => $p->manage_stock]);
        });
        $filter = $r->input('filter');
        if ($filter === 'low') $rows = $rows->filter(fn ($x) => $x['managed'] && $x['qty'] > 0 && $x['qty'] <= $x['threshold']);
        if ($filter === 'out') $rows = $rows->filter(fn ($x) => $x['managed'] && $x['qty'] <= 0);
        if ($t = trim((string) $r->q)) $rows = $rows->filter(fn ($x) => stripos($x['label'], $t) !== false || stripos((string) $x['sku'], $t) !== false);
        $page = max(1, (int) $r->page); $per = 25;
        $slice = new \Illuminate\Pagination\LengthAwarePaginator($rows->slice(($page - 1) * $per, $per)->values(), $rows->count(), $per, $page, ['path' => $r->url(), 'query' => $r->query()]);
        return view('admin.inventory.index', ['rows' => $slice, 'logs' => InventoryLog::with('product', 'user', 'variant.values.attribute')->latest()->limit(20)->get()]);
    }

    public function adjust(Request $r, InventoryService $inv)
    {
        $d = $r->validate(['product_id' => 'required|exists:products,id', 'variant_id' => 'nullable|exists:product_variants,id', 'mode' => 'required|in:add,set', 'quantity' => 'required|integer|min:0|max:9999999', 'note' => 'nullable|max:200']);
        $p = Product::findOrFail($d['product_id']);
        $v = $d['variant_id'] ? ProductVariant::where('product_id', $p->id)->findOrFail($d['variant_id']) : null;
        $current = $v ? $v->stock_qty : $p->stock_qty;
        $change = $d['mode'] === 'set' ? $d['quantity'] - $current : $d['quantity'];
        if ($current + $change < 0) return back()->withErrors(['stock' => 'Stock cannot go below zero.']);
        if ($change === 0) return back();
        if (!$v && !$p->manage_stock) $p->update(['manage_stock' => true]);
        $inv->adjust($p, $v, $change, 'manual', $d['note'] ?? null);
        return back()->with('status', 'Stock updated.');
    }

    public function history(Request $r)
    {
        return view('admin.inventory.history', ['logs' => InventoryLog::with('product', 'user', 'variant.values.attribute')->latest()->paginate(40)]);
    }
}
