<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('New conversation');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);                                   // user | assistant
            $table->longText('content');
            $table->unsignedInteger('tokens')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_tool_id')->constrained('ai_tools')->restrictOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->json('input')->nullable();
            $table->longText('output');
            $table->string('output_format', 10)->default('text');          // text | json
            $table->unsignedInteger('credits_used')->default(0);
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['ai_tool_id', 'created_at']);
        });

        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index();     // signup|renewal|usage|admin_add|admin_deduct|expiry
            $table->integer('amount');               // signed
            $table->unsignedInteger('balance_after');
            $table->string('description')->nullable();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_tool_id')->nullable()->constrained('ai_tools')->nullOnDelete();
            $table->string('provider', 50);
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->string('status', 20)->index();   // success | error
            $table->string('error_code', 50)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedInteger('credits_charged')->default(0);
            $table->timestamps();
            $table->index(['created_at']);
        });

        Schema::create('saved_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->string('category', 60)->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('uploaded_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('char_count')->default(0);
            $table->boolean('truncated')->default(false);
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['uploaded_documents', 'saved_contents', 'usage_logs', 'credit_transactions', 'ai_generations', 'messages', 'conversations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
