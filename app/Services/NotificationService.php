<?php
namespace App\Services;

use App\Models\Notification;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Order notification architecture. Every message is logged in the `notifications` table.
 * Email uses Laravel's mailer (configure SMTP in Admin → Email Settings / .env).
 * WhatsApp uses the WhatsApp Cloud API; it only sends when valid credentials are saved
 * in Admin → WhatsApp Settings, otherwise the entry is stored with status "skipped".
 */
class NotificationService
{
    public function orderPlaced(Order $order): void
    {
        $body = "Hi {$order->customer_name}, thank you for your order {$order->number} at Turtle Boss.\n"
            . 'Total: ' . money($order->total) . " ({$order->payment_label}).\nWe will notify you when it ships.";
        $this->email($order, 'order_placed', $order->customer_email, "Your Turtle Boss order {$order->number}", $body);
        $this->whatsapp($order, 'order_placed', $order->customer_phone, $body);
        if ($admin = setting('notify_admin_email')) {
            $this->email($order, 'admin_new_order', $admin, "New order {$order->number}", "New order {$order->number} for " . money($order->total) . " from {$order->customer_name}.");
        }
    }

    public function statusChanged(Order $order): void
    {
        $body = "Your Turtle Boss order {$order->number} is now: {$order->status_label}."
            . ($order->tracking_number ? " Tracking: {$order->tracking_number}" . ($order->courier ? " ({$order->courier})" : '') : '');
        $this->email($order, 'status_changed', $order->customer_email, "Order {$order->number} update", $body);
        $this->whatsapp($order, 'status_changed', $order->customer_phone, $body);
    }

    private function log(Order $order, string $channel, string $event, string $to, ?string $subject, string $body): Notification
    {
        return Notification::create(['channel' => $channel, 'event' => $event, 'recipient' => $to, 'subject' => $subject, 'body' => $body,
            'notifiable_type' => Order::class, 'notifiable_id' => $order->id]);
    }

    private function email(Order $order, string $event, string $to, string $subject, string $body): void
    {
        $n = $this->log($order, 'email', $event, $to, $subject, $body);
        if (setting('email_enabled', '1') !== '1') { $n->update(['status' => 'skipped', 'error' => 'Email notifications disabled']); return; }
        try {
            if (config('mail.default') === 'log' && !setting('mail_host')) {
                $n->update(['status' => 'skipped', 'error' => 'No mail transport configured (MAIL_MAILER=log)']);
                return;
            }
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
            $n->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $n->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }

    private function whatsapp(Order $order, string $event, string $to, string $body): void
    {
        $n = $this->log($order, 'whatsapp', $event, $to, null, $body);
        $token = setting('whatsapp_token'); $phoneId = setting('whatsapp_phone_id');
        if (setting('whatsapp_enabled') !== '1' || !$token || !$phoneId) {
            $n->update(['status' => 'skipped', 'error' => 'WhatsApp not configured']);
            return;
        }
        try {
            $digits = preg_replace('/\D/', '', $to);
            if (str_starts_with($digits, '0')) $digits = '92' . substr($digits, 1);
            $res = Http::withToken($token)->timeout(10)->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp', 'to' => $digits, 'type' => 'text', 'text' => ['body' => $body],
            ]);
            $res->successful() ? $n->update(['status' => 'sent']) : $n->update(['status' => 'failed', 'error' => mb_substr($res->body(), 0, 500)]);
        } catch (\Throwable $e) {
            $n->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500)]);
        }
    }
}
