<?php

namespace App\Http\Controllers;

use App\Models\SavedPrompt;
use App\Models\Tag;

class LandingController extends Controller
{
    public function index()
    {
        $publicPrompts = SavedPrompt::with('user')
            ->where('is_public', true)
            ->orderByDesc('likes_count')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('landing.index', [
            'totalTags'     => Tag::count(),
            'publicPrompts' => $publicPrompts,
        ]);
    }
}
