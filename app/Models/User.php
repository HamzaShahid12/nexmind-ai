<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    // Privileged columns (status, credits_balance, plan_id) are deliberately NOT mass assignable.
    protected $fillable = ['name', 'email', 'password', 'language', 'tone', 'notify_email'];

    protected $hidden = ['password', 'remember_token', 'avatar_path'];

    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'credits_reset_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'credits_balance' => 'integer',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    public function plan(): BelongsTo { return $this->belongsTo(Plan::class); }
    public function generations(): HasMany { return $this->hasMany(AiGeneration::class); }
    public function conversations(): HasMany { return $this->hasMany(Conversation::class); }
    public function savedContents(): HasMany { return $this->hasMany(SavedContent::class); }
    public function creditTransactions(): HasMany { return $this->hasMany(CreditTransaction::class); }
    public function documents(): HasMany { return $this->hasMany(UploadedDocument::class); }
    public function usageLogs(): HasMany { return $this->hasMany(UsageLog::class); }
}
