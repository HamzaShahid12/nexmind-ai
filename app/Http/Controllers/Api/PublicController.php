<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Plan;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function plans()
    {
        return response()->json(['data' => Plan::query()->where('is_active', true)->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description', 'monthly_credits', 'price', 'features'])]);
    }

    public function faqs()
    {
        return response()->json(['data' => Faq::query()->where('is_published', true)->orderBy('category')->orderBy('sort_order')
            ->get(['id', 'category', 'question', 'answer'])]);
    }

    public function blogCategories()
    {
        return response()->json(['data' => BlogCategory::query()->orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function blogPosts(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100']]);

        $posts = BlogPost::query()->published()->with('category:id,name,slug')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.addcslashes($request->search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->latest('published_at')
            ->paginate(12);

        $posts->getCollection()->each->makeHidden('content');

        return $posts;
    }

    public function blogPost(string $slug)
    {
        $post = BlogPost::query()->published()->with('category:id,name,slug')->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $post]);
    }

    public function contact(ContactRequest $request)
    {
        ContactMessage::create($request->validated());

        return response()->json(['message' => 'Thank you. Your message has been received.'], 201);
    }
}
