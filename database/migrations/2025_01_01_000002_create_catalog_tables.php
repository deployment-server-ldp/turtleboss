<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('image')->nullable();
            $t->text('description')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['parent_id', 'sort_order']);
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('sku')->nullable()->unique();
            $t->string('barcode')->nullable();
            $t->string('brand')->nullable()->index();
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->text('short_description')->nullable();
            $t->longText('description')->nullable();
            $t->decimal('price', 12, 2)->default(0);
            $t->decimal('sale_price', 12, 2)->nullable();
            $t->decimal('cost_price', 12, 2)->nullable();
            $t->boolean('manage_stock')->default(true);
            $t->integer('stock_qty')->default(0);
            $t->unsignedInteger('low_stock_threshold')->default(5);
            $t->string('stock_status')->default('in_stock'); // in_stock|out_of_stock|backorder
            $t->decimal('weight', 8, 2)->nullable();
            $t->decimal('length', 8, 2)->nullable();
            $t->decimal('width', 8, 2)->nullable();
            $t->decimal('height', 8, 2)->nullable();
            $t->text('shipping_info')->nullable();
            $t->string('video_url')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->boolean('is_featured')->default(false)->index();
            $t->boolean('is_bestseller')->default(false)->index();
            $t->boolean('is_new')->default(false)->index();
            $t->boolean('is_trending')->default(false)->index();
            $t->boolean('is_demo')->default(false);
            $t->decimal('rating_avg', 3, 2)->default(0);
            $t->unsignedInteger('rating_count')->default(0);
            $t->unsignedInteger('sales_count')->default(0);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('category_product', function (Blueprint $t) {
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->primary(['category_id', 'product_id']);
        });

        Schema::create('attributes', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type')->default('select'); // select|color|text
            $t->boolean('is_filterable')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('attribute_values', function (Blueprint $t) {
            $t->id();
            $t->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $t->string('value');
            $t->string('color_hex', 9)->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        // Specification rows shown on the product page (e.g. Material: Plastic)
        Schema::create('product_attributes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('attribute_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('value');
            $t->unsignedInteger('sort_order')->default(0);
        });
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('sku')->nullable()->unique();
            $t->decimal('price', 12, 2)->nullable();
            $t->decimal('sale_price', 12, 2)->nullable();
            $t->integer('stock_qty')->default(0);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('variant_attribute_values', function (Blueprint $t) {
            $t->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $t->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $t->primary(['variant_id', 'attribute_value_id']);
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $t->string('path');
            $t->string('alt')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        Schema::create('product_relations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('related_id')->constrained('products')->cascadeOnDelete();
            $t->string('type'); // related|cross_sell|upsell
            $t->unique(['product_id', 'related_id', 'type']);
        });
        Schema::create('inventory_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $t->integer('change');
            $t->integer('qty_after');
            $t->string('reason'); // manual|order|cancel|refund|import
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['inventory_logs','product_relations','product_images','variant_attribute_values','product_variants','product_attributes','attribute_values','attributes','category_product','products','categories'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
