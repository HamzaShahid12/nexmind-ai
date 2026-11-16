<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    use SoftDeletes;

    protected $fillable = ['blog_category_id', 'title', 'slug', 'excerpt', 'content', 'featured_image_path', 'status', 'published_at', 'meta_title', 'meta_description'];

    protected $hidden = ['featured_image_path'];

    protected $appends = ['featured_image'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getFeaturedImageAttribute(): ?string
    {
        return $this->featured_image_path ? Storage::disk('public')->url($this->featured_image_path) : null;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function category(): BelongsTo { return $this->belongsTo(BlogCategory::class, 'blog_category_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
