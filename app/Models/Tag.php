<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'section',
        'subsection',
        'post_count',
        'is_nsfw',
    ];

    protected $casts = [
        'is_nsfw' => 'boolean',
        'post_count' => 'integer',
    ];

    public function scopeBySection(Builder $query, string $section): Builder
    {
        return $query->where('section', $section);
    }

    public function scopeBySubsection(Builder $query, string $subsection): Builder
    {
        return $query->where('subsection', $subsection);
    }

    public function scopeSfw(Builder $query): Builder
    {
        return $query->where('is_nsfw', false);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('post_count');
    }
}
