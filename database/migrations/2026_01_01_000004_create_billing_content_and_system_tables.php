<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('active');   // active | canceled | expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        // Structure only. No payment gateway is integrated and no rows are seeded.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 50);
            $table->string('provider_reference')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image_path')->nullable();
            $table->string('status', 20)->default('draft');     // draft | published
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 80)->default('General')->index();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100)->index();
            $table->nullableMorphs('subject');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'settings', 'notifications', 'contact_messages', 'faqs', 'blog_posts', 'blog_categories', 'payments', 'subscriptions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
