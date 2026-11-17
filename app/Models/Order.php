<?php
namespace App\Models;

class Order extends Model
{
    public const STATUSES = ['pending' => 'Pending', 'processing' => 'Processing', 'on_hold' => 'On Hold', 'shipped' => 'Shipped', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
    public const PAYMENT_METHODS = ['cod' => 'Cash on Delivery', 'bank_transfer' => 'Bank Transfer', 'gateway' => 'Online Payment'];

    public function items() { return $this->hasMany(OrderItem::class); }
    public function notes() { return $this->hasMany(OrderNote::class)->latest(); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function shipments() { return $this->hasMany(Shipment::class); }
    public function refunds() { return $this->hasMany(Refund::class)->latest(); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->status] ?? ucfirst($this->status); }
    public function getPaymentLabelAttribute(): string { return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method; }
}
