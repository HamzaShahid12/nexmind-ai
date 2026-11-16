<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('monthly_credits')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_tools', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 50)->index();
            $table->unsignedInteger('credit_cost')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('daily_limit')->nullable();      // per-user generations per day
            $table->unsignedInteger('max_output_tokens')->default(1500);
            $table->decimal('temperature', 3, 2)->nullable()->default(0.70);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Which tools a plan includes. A plan with no rows includes every active tool.
        Schema::create('plan_ai_tool', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_tool_id')->constrained('ai_tools')->cascadeOnDelete();
            $table->primary(['plan_id', 'ai_tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_ai_tool');
        Schema::dropIfExists('ai_tools');
        Schema::dropIfExists('plans');
    }
};
