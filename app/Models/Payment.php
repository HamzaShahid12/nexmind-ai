<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Placeholder for a future payment gateway integration. Nothing creates payments yet. */
class Payment extends Model
{
    protected $fillable = ['user_id', 'subscription_id', 'provider', 'provider_reference', 'amount', 'currency', 'status', 'paid_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount' => 'decimal:2'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
}
