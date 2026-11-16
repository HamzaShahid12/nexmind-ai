<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadedDocument extends Model
{
    protected $fillable = ['user_id', 'original_name', 'mime', 'size', 'extracted_text', 'char_count', 'truncated', 'expires_at'];

    // Extracted text is only exposed through explicit AI calls, never in API responses.
    protected $hidden = ['extracted_text'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'truncated' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
