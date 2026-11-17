<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('session_id')->nullable()->index();
            $t->string('coupon_code')->nullable();
            $t->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $t->unsignedInteger('quantity')->default(1);
            $t->timestamps();
        });

        Schema::create('coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('description')->nullable();
            $t->string('type'); // percent|fixed|free_shipping
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('min_spend', 12, 2)->nullable();
            $t->decimal('max_discount', 12, 2)->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('per_user_limit')->nullable();
            $t->unsignedInteger('used_count')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('coupon_product', function (Blueprint $t) {
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['coupon_id', 'product_id']);
        });
        Schema::create('coupon_category', function (Blueprint $t) {
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->primary(['coupon_id', 'category_id']);
        });

        Schema::create('shipping_rules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('city')->nullable(); // null = applies to all cities
            $t->decimal('rate', 10, 2)->default(0);
            $t->decimal('free_over', 12, 2)->nullable();
            $t->unsignedInteger('priority')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('number')->unique();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('pending')->index();
            $t->string('payment_method'); // cod|bank_transfer|gateway
            $t->string('payment_status')->default('unpaid'); // unpaid|paid|refunded|partially_refunded
            $t->string('customer_name');
            $t->string('customer_email');
            $t->string('customer_phone', 32);
            $t->string('shipping_address');
            $t->string('shipping_city');
            $t->string('shipping_province')->nullable();
            $t->string('shipping_postal_code', 16)->nullable();
            $t->text('customer_notes')->nullable();
            $t->decimal('subtotal', 12, 2);
            $t->decimal('discount_total', 12, 2)->default(0);
            $t->decimal('shipping_total', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->decimal('refunded_total', 12, 2)->default(0);
            $t->string('coupon_code')->nullable();
            $t->string('tracking_number')->nullable();
            $t->string('courier')->nullable();
            $t->timestamps();
            $t->index('created_at');
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $t->string('name');
            $t->string('variant_label')->nullable();
            $t->string('sku')->nullable();
            $t->string('image')->nullable();
            $t->decimal('unit_price', 12, 2);
            $t->decimal('cost_price', 12, 2)->nullable();
            $t->unsignedInteger('quantity');
            $t->decimal('line_total', 12, 2);
        });
        Schema::create('order_notes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->text('note');
            $t->boolean('is_status_change')->default(false);
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('method');
            $t->string('status')->default('pending'); // pending|paid|failed|refunded
            $t->decimal('amount', 12, 2);
            $t->string('reference')->nullable();
            $t->text('meta')->nullable();
            $t->timestamps();
        });
        Schema::create('shipments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->string('courier')->nullable();
            $t->string('tracking_number')->nullable();
            $t->timestamp('shipped_at')->nullable();
            $t->timestamps();
        });
        Schema::create('refunds', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('reason')->nullable();
            $t->boolean('restock')->default(false);
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('coupon_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('email')->nullable();
            $t->decimal('discount', 12, 2);
            $t->timestamps();
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->unsignedTinyInteger('rating');
            $t->string('title')->nullable();
            $t->text('body');
            $t->string('image')->nullable();
            $t->boolean('is_verified')->default(false);
            $t->boolean('is_approved')->default(false)->index();
            $t->timestamps();
        });
        Schema::create('wishlists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('session_id')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('wishlist_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wishlist_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['wishlist_id', 'product_id']);
        });
    }
    public function down(): void
    {
        foreach (['wishlist_items','wishlists','reviews','coupon_usages','refunds','shipments','payments','order_notes','order_items','orders','shipping_rules','coupon_category','coupon_product','coupons','cart_items','carts'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
