<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/** Settings pages (general, payment, email, whatsapp, seo). Each group declares its keys + validation. */
class SettingController extends Controller
{
    private function groups(): array
    {
        return [
            'general' => ['title' => 'General Settings', 'perm' => 'settings', 'fields' => [
                ['site_name', 'Store name', 'text', 'required|max:80'], ['tagline', 'Tagline', 'text', 'nullable|max:160'],
                ['contact_email', 'Contact email', 'text', 'nullable|email'], ['contact_phone', 'Contact phone', 'text', 'nullable|max:40'],
                ['whatsapp_number', 'WhatsApp number (e.g. 923001234567)', 'text', 'nullable|max:20'], ['contact_address', 'Store address', 'textarea', 'nullable|max:500'],
                ['logo', 'Logo image (upload your official Turtle Boss logo here; used in header/footer/emails)', 'image', 'nullable'],
                ['favicon', 'Favicon', 'image', 'nullable'],
                ['announcement_enabled', 'Show announcement bar', 'checkbox', 'nullable'], ['announcement_messages', 'Announcement messages (one per line, rotates)', 'textarea', 'nullable|max:1000'],
                ['slider_interval', 'Hero slider duration (milliseconds)', 'number', 'nullable|integer|min:1500|max:30000'],
                ['low_stock_default', 'Default low-stock threshold', 'number', 'nullable|integer|min:0'],
                ['social_facebook', 'Facebook URL', 'text', 'nullable|url'], ['social_instagram', 'Instagram URL', 'text', 'nullable|url'], ['social_tiktok', 'TikTok URL', 'text', 'nullable|url'],
            ]],
            'payment' => ['title' => 'Payment Settings', 'perm' => 'settings', 'fields' => [
                ['pay_cod_enabled', 'Enable Cash on Delivery', 'checkbox', 'nullable'], ['pay_bank_enabled', 'Enable Bank Transfer', 'checkbox', 'nullable'],
                ['bank_details', 'Bank transfer instructions (shown at checkout and in emails)', 'textarea', 'nullable|max:1000'],
                ['pay_gateway_enabled', 'Enable Online Payment Gateway (needs a gateway integration + credentials)', 'checkbox', 'nullable'],
                ['gateway_name', 'Gateway name (e.g. PayFast, Safepay, Stripe)', 'text', 'nullable|max:60'], ['gateway_key', 'Gateway public/merchant key', 'text', 'nullable|max:255'], ['gateway_secret', 'Gateway secret (stored encrypted-at-rest by your DB/host, never shown to customers)', 'password', 'nullable|max:255'],
            ], 'note' => 'Online payment is architecture-ready: orders already carry payment method/status and Payment records. A gateway adapter must be written for your chosen provider and tested with real credentials; until then the Online Payment option stays hidden at checkout.'],
            'email' => ['title' => 'Email Settings', 'perm' => 'settings', 'fields' => [
                ['email_enabled', 'Send order emails', 'checkbox', 'nullable'], ['notify_admin_email', 'Notify this address of new orders', 'text', 'nullable|email'],
                ['mail_host', 'SMTP host (informational — SMTP is configured in the server .env: MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD)', 'text', 'nullable|max:120'],
            ], 'note' => 'Emails are sent through Laravel mail. Set MAIL_MAILER=smtp and credentials in .env. Every email attempt (sent/failed/skipped) is logged on the order page.'],
            'whatsapp' => ['title' => 'WhatsApp Settings', 'perm' => 'settings', 'fields' => [
                ['whatsapp_enabled', 'Enable WhatsApp order notifications', 'checkbox', 'nullable'], ['whatsapp_phone_id', 'WhatsApp Cloud API — Phone number ID', 'text', 'nullable|max:60'], ['whatsapp_token', 'WhatsApp Cloud API — permanent access token', 'password', 'nullable|max:600'],
            ], 'note' => 'Uses the Meta WhatsApp Cloud API. Nothing is sent until valid credentials are saved here. Free-form text messages only reach customers inside WhatsApp’s 24-hour window; for business-initiated messages create approved templates in Meta Business Manager.'],
            'seo' => ['title' => 'Global SEO', 'perm' => 'seo', 'fields' => [
                ['seo_title', 'Website title', 'text', 'required|max:160'], ['seo_description', 'Default meta description', 'textarea', 'nullable|max:320'],
                ['seo_image', 'Default SEO / social share image', 'image', 'nullable'],
                ['robots_txt', 'robots.txt content (Sitemap line is appended automatically)', 'textarea', 'nullable|max:3000'],
                ['ga_id', 'Google Analytics 4 Measurement ID (G-XXXXXXX)', 'text', 'nullable|regex:/^G-[A-Z0-9]+$/i'], ['gsc_verification', 'Google Search Console verification code (meta content value)', 'text', 'nullable|max:100'],
            ]],
            'shipping' => ['title' => 'Shipping Settings', 'perm' => 'settings', 'fields' => [
                ['shipping_flat_rate', 'Default flat rate (Rs.) – used when no rule matches', 'number', 'nullable|numeric|min:0'], ['shipping_free_threshold', 'Free shipping threshold (Rs.; empty = none)', 'number', 'nullable|numeric|min:0'],
                ['shipping_info', 'Shipping information shown on product pages', 'textarea', 'nullable|max:500'], ['payment_info', 'Payment information shown on product pages', 'textarea', 'nullable|max:500'],
            ], 'extra' => 'shipping'],
        ];
    }

    public function show(string $group)
    {
        $g = $this->groups()[$group] ?? abort(404);
        abort_unless(auth()->user()->hasPermission($g['perm']), 403);
        return view('admin.settings', ['group' => $group, 'g' => $g, 'tabs' => $this->groups(), 'rules' => \App\Models\ShippingRule::orderBy('priority')->get()]);
    }

    public function update(Request $r, string $group, ImageService $images)
    {
        $g = $this->groups()[$group] ?? abort(404);
        abort_unless(auth()->user()->hasPermission($g['perm']), 403);
        $rules = [];
        foreach ($g['fields'] as [$k, , $t, $rule]) $rules[$k] = $t === 'image' ? 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:4096' : $rule;
        $r->validate($rules);
        foreach ($g['fields'] as [$k, , $t]) {
            if ($t === 'image') {
                if ($r->hasFile($k)) { $images->delete(Setting::get($k)); Setting::put($k, $images->store($r->file($k), 'branding')); }
                elseif ($r->boolean($k . '_remove')) { $images->delete(Setting::get($k)); Setting::put($k, null); }
            } elseif ($t === 'checkbox') Setting::put($k, $r->boolean($k) ? '1' : '0');
            elseif ($t === 'password') { if ($r->filled($k)) Setting::put($k, $r->input($k)); }
            else Setting::put($k, $r->input($k));
        }
        Cache::forget('sitemap.xml');
        return back()->with('status', $g['title'] . ' saved.');
    }
}
