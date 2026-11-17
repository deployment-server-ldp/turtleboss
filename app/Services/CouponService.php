<?php
namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Support\Collection;

class CouponService
{
    /**
     * Validate a coupon server-side against cart lines.
     * @param Collection $lines cart lines (see CartService::lines)
     * @return array{coupon: Coupon, discount: float, free_shipping: bool}
     * @throws \InvalidArgumentException with a customer-friendly message
     */
    public function evaluate(string $code, Collection $lines, float $subtotal, ?int $userId = null, ?string $email = null): array
    {
        $coupon = Coupon::with(['products:id', 'categories:id'])->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->first();
        if (!$coupon || !$coupon->is_active) throw new \InvalidArgumentException('This coupon code is not valid.');
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) throw new \InvalidArgumentException('This coupon is not active yet.');
        if ($coupon->expires_at && $coupon->expires_at->isPast()) throw new \InvalidArgumentException('This coupon has expired.');
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) throw new \InvalidArgumentException('This coupon has reached its usage limit.');
        if ($coupon->per_user_limit !== null && ($userId || $email)) {
            $used = CouponUsage::where('coupon_id', $coupon->id)->where(fn ($q) => $userId ? $q->where('user_id', $userId) : $q->where('email', $email))->count();
            if ($used >= $coupon->per_user_limit) throw new \InvalidArgumentException('You have already used this coupon the maximum number of times.');
        }
        if ($coupon->min_spend !== null && $subtotal < $coupon->min_spend) {
            throw new \InvalidArgumentException('Minimum spend of ' . money($coupon->min_spend) . ' required for this coupon.');
        }

        // Eligible subtotal: restrict to chosen products/categories when set.
        $pIds = $coupon->products->pluck('id')->all();
        $cIds = $coupon->categories->pluck('id')->all();
        $eligible = $lines;
        if ($pIds || $cIds) {
            $eligible = $lines->filter(function ($l) use ($pIds, $cIds) {
                if (in_array($l['product']->id, $pIds)) return true;
                if ($cIds) {
                    $own = $l['product']->categories->pluck('id')->push($l['product']->category_id)->filter()->all();
                    return (bool) array_intersect($own, $cIds);
                }
                return false;
            });
            if ($eligible->isEmpty()) throw new \InvalidArgumentException('This coupon does not apply to the items in your cart.');
        }
        $base = (float) $eligible->sum('line_total');

        $discount = 0.0;
        $freeShipping = false;
        if ($coupon->type === 'percent') {
            $discount = $base * min(100, $coupon->amount) / 100;
        } elseif ($coupon->type === 'fixed') {
            $discount = min($coupon->amount, $base);
        } elseif ($coupon->type === 'free_shipping') {
            $freeShipping = true;
        }
        if ($coupon->max_discount !== null) $discount = min($discount, $coupon->max_discount);
        return ['coupon' => $coupon, 'discount' => round(min($discount, $subtotal), 2), 'free_shipping' => $freeShipping];
    }
}
