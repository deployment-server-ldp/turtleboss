<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
        Schema::create('sliders', function (Blueprint $t) {
            $t->id();
            $t->string('heading')->nullable();
            $t->string('subheading')->nullable();
            $t->string('cta_text')->nullable();
            $t->string('cta_url')->nullable();
            $t->string('image_desktop')->nullable();
            $t->string('image_mobile')->nullable();
            $t->string('alt')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('menus', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('location')->unique(); // header|footer_shop|footer_help|mobile
            $t->timestamps();
        });
        Schema::create('menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $t->string('label');
            $t->string('url');
            $t->string('type')->default('link'); // link|mega_categories
            $t->boolean('open_new_tab')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->longText('content')->nullable();
            $t->string('banner')->nullable();
            $t->string('template')->default('default'); // default|contact|faq
            $t->boolean('is_published')->default(true);
            $t->timestamps();
        });
        Schema::create('blog_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('blog_tags', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('blog_posts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('excerpt')->nullable();
            $t->longText('content')->nullable();
            $t->string('featured_image')->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('blog_post_tag', function (Blueprint $t) {
            $t->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $t->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $t->primary(['blog_post_id', 'blog_tag_id']);
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('path');
            $t->string('filename');
            $t->string('mime', 64);
            $t->unsignedBigInteger('size')->default(0);
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->string('title')->nullable();
            $t->string('alt')->nullable();
            $t->timestamps();
        });
        Schema::create('seo_metadata', function (Blueprint $t) {
            $t->id();
            $t->morphs('seoable');
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->string('focus_keyword')->nullable();
            $t->string('canonical_url')->nullable();
            $t->string('og_title')->nullable();
            $t->text('og_description')->nullable();
            $t->string('og_image')->nullable();
            $t->boolean('noindex')->default(false);
            $t->timestamps();
            $t->unique(['seoable_type', 'seoable_id']);
        });
        Schema::create('redirects', function (Blueprint $t) {
            $t->id();
            $t->string('from_path')->unique();
            $t->string('to_url');
            $t->unsignedSmallInteger('status_code')->default(301);
            $t->unsignedInteger('hits')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('homepage_sections', function (Blueprint $t) {
            $t->id();
            $t->string('type'); // categories|products|banners|why_choose|testimonials|newsletter|rich_text
            $t->string('title')->nullable();
            $t->string('subtitle')->nullable();
            $t->json('config')->nullable();
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->string('channel'); // email|whatsapp
            $t->string('event');
            $t->string('recipient');
            $t->string('subject')->nullable();
            $t->text('body')->nullable();
            $t->string('status')->default('queued'); // queued|sent|failed|skipped
            $t->text('error')->nullable();
            $t->nullableMorphs('notifiable');
            $t->timestamps();
        });
        Schema::create('newsletter_subscribers', function (Blueprint $t) {
            $t->id();
            $t->string('email')->unique();
            $t->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('phone', 32)->nullable();
            $t->text('message');
            $t->boolean('is_read')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['contact_messages','newsletter_subscribers','notifications','homepage_sections','redirects','seo_metadata','media','blog_post_tag','blog_posts','blog_tags','blog_categories','pages','menu_items','menus','sliders','settings'] as $n) {
            Schema::dropIfExists($n);
        }
    }
};
