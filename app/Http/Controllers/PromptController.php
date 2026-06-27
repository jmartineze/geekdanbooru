<?php

namespace App\Http\Controllers;

use App\Models\SavedPrompt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromptController extends Controller
{
    public function index()
    {
        $prompts = auth()->user()
            ->savedPrompts()
            ->latest()
            ->get();

        return view('prompts.my-prompts', compact('prompts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'prompt_text' => ['required', 'string', 'max:5000'],
            'section'     => ['required', 'in:full,character,pose,outfit,scene'],
            'image'       => ['nullable', 'image', 'max:1024', 'mimes:jpeg,png,gif,webp'],
            'disclaimer'  => $request->hasFile('image') ? ['accepted'] : ['nullable'],
        ], [
            'disclaimer.accepted' => 'You must accept the image disclaimer to upload.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')
                ->store('prompts/' . auth()->id(), 'public');
        }

        $prompt = auth()->user()->savedPrompts()->create([
            'name'        => $data['name'],
            'prompt_text' => $data['prompt_text'],
            'section'     => $data['section'],
            'image_path'  => $imagePath,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['id' => $prompt->id, 'message' => 'Prompt saved!'], 201);
        }

        return back()->with('success', 'Prompt saved!');
    }

    public function update(Request $request, SavedPrompt $prompt)
    {
        $this->authorize('update', $prompt);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:120'],
            'prompt_text' => ['sometimes', 'string', 'max:5000'],
            'section'     => ['sometimes', 'in:full,character,pose,outfit,scene'],
            'is_public'   => ['sometimes', 'boolean'],
            'image'       => ['sometimes', 'nullable', 'image', 'max:1024', 'mimes:jpeg,png,gif,webp'],
            'disclaimer'  => $request->hasFile('image') ? ['accepted'] : ['nullable'],
        ], [
            'disclaimer.accepted' => 'You must accept the image disclaimer to upload.',
        ]);

        if ($request->hasFile('image')) {
            if ($prompt->image_path) {
                Storage::disk('public')->delete($prompt->image_path);
            }
            $data['image_path'] = $request->file('image')
                ->store('prompts/' . auth()->id(), 'public');
        }

        unset($data['image'], $data['disclaimer']);
        $prompt->update($data);

        return response()->json([
            'message'    => 'Updated.',
            'image_path' => $prompt->fresh()->image_path,
        ]);
    }

    public function destroy(SavedPrompt $prompt)
    {
        $this->authorize('delete', $prompt);

        if ($prompt->image_path) {
            Storage::disk('public')->delete($prompt->image_path);
        }

        $prompt->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
