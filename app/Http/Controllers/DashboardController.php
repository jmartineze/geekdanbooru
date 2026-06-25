<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $structure = [
            'character' => Tag::bySection('character')
                ->select('subsection')
                ->distinct()
                ->orderBy('subsection')
                ->pluck('subsection'),
            'pose' => Tag::bySection('pose')
                ->select('subsection')
                ->distinct()
                ->orderBy('subsection')
                ->pluck('subsection'),
            'outfit' => Tag::bySection('outfit')
                ->select('subsection')
                ->distinct()
                ->orderBy('subsection')
                ->pluck('subsection'),
            'scene' => Tag::bySection('scene')
                ->select('subsection')
                ->distinct()
                ->orderBy('subsection')
                ->pluck('subsection'),
        ];

        $totalTags = Tag::count();

        return view('dashboard.index', compact('structure', 'totalTags'));
    }
}
