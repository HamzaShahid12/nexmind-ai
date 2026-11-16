<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FaqController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Faq::query()->orderBy('category')->orderBy('sort_order')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sort_order'] ??= (int) Faq::query()->max('sort_order') + 1;
        $faq = Faq::create($data);
        ActivityLogger::log('admin.faq.created', $faq);

        return response()->json(['data' => $faq], 201);
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request));

        return response()->json(['data' => $faq]);
    }

    public function destroy(Faq $faq)
    {
        ActivityLogger::log('admin.faq.deleted', $faq);
        $faq->delete();

        return response()->json(['message' => 'FAQ deleted.']);
    }

    /** Body: {"ids": [3,1,2]} - the new order, top to bottom. */
    public function reorder(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'exists:faqs,id']]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['ids']) as $position => $id) {
                Faq::whereKey($id)->update(['sort_order' => $position]);
            }
        });

        return response()->json(['message' => 'Order saved.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['boolean'],
        ]);
    }
}
