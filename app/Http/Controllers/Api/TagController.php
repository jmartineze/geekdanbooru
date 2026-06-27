<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'section'    => 'required|in:character,pose,outfit,scene',
            'subsection' => 'sometimes|string|max:100',
            'nsfw'       => 'sometimes|boolean',
            'q'          => 'sometimes|string|max:100',
        ]);

        $query = Tag::bySection($request->section)->popular();

        if ($request->filled('subsection')) {
            $query->bySubsection($request->subsection);
        }

        if (!$request->boolean('nsfw')) {
            $query->sfw();
        }

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $tags = $query->select('id', 'name', 'subsection', 'post_count', 'is_nsfw')
            ->limit(500)
            ->get();

        return response()->json($tags);
    }

    public function resolve(Request $request): JsonResponse
    {
        $names = collect(explode(',', $request->input('names', '')))
            ->map(fn($n) => trim($n))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return response()->json([]);
        }

        $tags = Tag::whereIn('name', $names)
            ->select('id', 'name', 'section', 'subsection', 'post_count', 'is_nsfw')
            ->get()
            ->keyBy('name');

        // Preserve original order and include unknown tags as stubs
        $result = $names->map(fn($name) => $tags->get($name))->filter()->values();

        return response()->json($result);
    }
}
