<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    // ---- posts ---------------------------------------------------------------
    public function index(Request $request)
    {
        return BlogPost::query()->with('category:id,name')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.addcslashes($request->search, '%_\\').'%'))
            ->latest()->paginate($request->integer('per_page', 15));
    }

    public function show(BlogPost $post)
    {
        return response()->json(['data' => $post->load('category:id,name')]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['title']);
        $data = $this->withImage($request, $data);

        $post = BlogPost::create($data);
        $post->forceFill(['author_id' => $request->user()->id])->save();
        $this->syncPublishState($post);
        ActivityLogger::log('admin.blog.created', $post);

        return response()->json(['data' => $post->fresh('category')], 201);
    }

    public function update(Request $request, BlogPost $post)
    {
        $data = $this->validated($request, $post);
        // Keep the existing URL unless a new slug is explicitly supplied (avoids breaking published links).
        $data['slug'] = ! empty($data['slug']) ? $this->uniqueSlug($data['slug'], $post->id) : $post->slug;
        $data = $this->withImage($request, $data, $post);

        $post->update($data);
        $this->syncPublishState($post);
        ActivityLogger::log('admin.blog.updated', $post);

        return response()->json(['data' => $post->fresh('category')]);
    }

    public function publish(BlogPost $post)
    {
        $post->update(['status' => 'published', 'published_at' => $post->published_at ?? now()]);
        ActivityLogger::log('admin.blog.published', $post);

        return response()->json(['data' => $post->fresh()]);
    }

    public function unpublish(BlogPost $post)
    {
        $post->update(['status' => 'draft']);
        ActivityLogger::log('admin.blog.unpublished', $post);

        return response()->json(['data' => $post->fresh()]);
    }

    public function destroy(BlogPost $post)
    {
        ActivityLogger::log('admin.blog.deleted', $post);
        $post->delete();

        return response()->json(['message' => 'Article deleted.']);
    }

    // ---- categories ----------------------------------------------------------
    public function categories()
    {
        return response()->json(['data' => BlogCategory::query()->withCount('posts')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:blog_categories,name']]);
        $category = BlogCategory::create($data + ['slug' => $this->uniqueCategorySlug($data['name'])]);
        ActivityLogger::log('admin.blog_category.created', $category);

        return response()->json(['data' => $category], 201);
    }

    public function updateCategory(Request $request, BlogCategory $category)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('blog_categories', 'name')->ignore($category->id)]]);
        $category->update($data + ['slug' => $this->uniqueCategorySlug($data['name'], $category->id)]);

        return response()->json(['data' => $category]);
    }

    public function destroyCategory(BlogCategory $category)
    {
        ActivityLogger::log('admin.blog_category.deleted', $category);
        $category->delete();                                   // posts keep existing, category set to null

        return response()->json(['message' => 'Category deleted.']);
    }

    // ---- helpers -------------------------------------------------------------
    private function validated(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string', 'max:200000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            // SVG is excluded on purpose (script injection risk).
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
    }

    private function withImage(Request $request, array $data, ?BlogPost $post = null): array
    {
        unset($data['image']);
        if ($request->hasFile('image')) {
            if ($post?->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
            $data['featured_image_path'] = $request->file('image')->store('blog', 'public');
        }

        return $data;
    }

    private function syncPublishState(BlogPost $post): void
    {
        if ($post->status === 'published' && ! $post->published_at) {
            $post->update(['published_at' => now()]);
        }
    }

    private function uniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'post';
        $slug = $base;
        $i = 2;
        while (BlogPost::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function uniqueCategorySlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;
        while (BlogCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
