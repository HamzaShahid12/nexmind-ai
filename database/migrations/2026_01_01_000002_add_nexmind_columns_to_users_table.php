<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('password')->constrained('plans')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();      // active | suspended
            $table->unsignedInteger('credits_balance')->default(0);        // never negative
            $table->timestamp('credits_reset_at')->nullable()->index();
            $table->string('avatar_path')->nullable();
            $table->string('language', 50)->default('English');
            $table->string('tone', 30)->default('professional');
            $table->boolean('notify_email')->default(true);
            $table->timestamp('deletion_requested_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'credits_balance', 'credits_reset_at', 'avatar_path', 'language', 'tone', 'notify_email', 'deletion_requested_at']);
        });
    }
};
