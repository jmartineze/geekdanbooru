<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing.index', [
            'totalTags' => Tag::count(),
        ]);
    }
}
