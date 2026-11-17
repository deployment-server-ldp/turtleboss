<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting(string $key, $default = null) { return Setting::get($key, $default); }
}
if (!function_exists('money')) {
    function money($amount): string
    {
        return 'Rs. ' . number_format((float) $amount, 0);
    }
}
if (!function_exists('img')) {
    /** Public URL for a stored image path (or a placeholder). */
    function img(?string $path, string $fallback = 'images/placeholder.svg'): string
    {
        if (!$path) return asset($fallback);
        if (str_starts_with($path, 'http')) return $path;
        return asset('storage/' . ltrim($path, '/'));
    }
}
