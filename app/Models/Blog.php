<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'image', 'category',
        'seo_title', 'seo_description', 'meta_title', 'meta_description',
        'meta_image', 'author', 'publisher', 'copyright', 'site_name', 'keywords',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function excerpt(int $limit = 180): string
    {
        return Str::limit(trim(strip_tags((string) $this->content)), $limit);
    }

    public function readingTime(): int
    {
        $wordCount = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($wordCount / 200));
    }

    public function publicImageUrl(?string $image = null): ?string
    {
        $path = $image ?? $this->image;

        if (! filled($path) || str_contains($path, '..')) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return in_array(strtolower((string) parse_url($path, PHP_URL_SCHEME)), ['http', 'https'], true)
                ? $path
                : null;
        }

        $localPath = ltrim($path, '/');

        if (preg_match('/\A[a-zA-Z0-9_\/ .-]+\z/', $localPath) !== 1) {
            return null;
        }

        return asset('storage/'.$localPath);
    }
}
