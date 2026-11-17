<?php
namespace App\Services;

use App\Models\ShippingRule;

class ShippingService
{
    /** Pakistani cities offered at checkout; extend via admin shipping rules. */
    public static function cities(): array
    {
        return ['Karachi','Lahore','Islamabad','Rawalpindi','Faisalabad','Multan','Peshawar','Quetta','Hyderabad','Sialkot','Gujranwala','Bahawalpur','Sargodha','Sukkur','Larkana','Abbottabad','Other'];
    }
    public static function provinces(): array
    {
        return ['Punjab','Sindh','Khyber Pakhtunkhwa','Balochistan','Islamabad Capital Territory','Azad Kashmir','Gilgit-Baltistan'];
    }
    /**
     * Shipping charge for a subtotal and city. City-specific active rules win over the global ones;
     * lowest priority number first. Falls back to settings flat rate + free threshold.
     */
    public function quote(float $subtotal, ?string $city = null, bool $freeShippingCoupon = false): array
    {
        if ($freeShippingCoupon) return ['amount' => 0.0, 'label' => 'Free shipping (coupon)', 'free' => true];
        $rules = ShippingRule::where('is_active', true)->orderBy('priority')->get();
        $rule = null;
        if ($city) $rule = $rules->first(fn ($r) => $r->city && strcasecmp($r->city, $city) === 0);
        $rule ??= $rules->first(fn ($r) => !$r->city);
        if ($rule) {
            $free = $rule->free_over !== null && $subtotal >= $rule->free_over;
            return ['amount' => $free ? 0.0 : (float) $rule->rate, 'label' => $rule->name, 'free' => $free];
        }
        $flat = (float) setting('shipping_flat_rate', 250);
        $threshold = setting('shipping_free_threshold');
        $free = $threshold !== null && $threshold !== '' && (float) $threshold > 0 && $subtotal >= (float) $threshold;
        return ['amount' => $free ? 0.0 : $flat, 'label' => 'Standard shipping', 'free' => $free];
    }
}
