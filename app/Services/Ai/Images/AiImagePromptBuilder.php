<?php

namespace App\Services\Ai\Images;

use App\Models\AiContentDraft;
use Illuminate\Support\Str;

class AiImagePromptBuilder
{
    /**
     * Build prompt for the image generation model.
     */
    public function buildPrompt(AiContentDraft $draft, ?string $userInstructions = null): string
    {
        $brand = $draft->brand;
        $title = $draft->seo_title ?: $draft->topic;
        $keyword = $draft->primary_keyword;

        $parts = [
            "Create a professional, high-resolution editorial featured image for a blog article titled '{$title}'.",
            "Core subject and focus: '{$keyword}'.",
        ];

        if ($brand !== null) {
            $brandContext = [];
            if ($brand->industry) {
                $brandContext[] = "industry: {$brand->industry}";
            }
            if ($brand->voice_tone) {
                $brandContext[] = "aesthetic and tone: {$brand->voice_tone}";
            }
            if ($brandContext !== []) {
                $parts[] = 'Brand context: '.implode(', ', $brandContext).'.';
            }
        }

        if (! empty($userInstructions)) {
            $parts[] = 'Specific visual style and requirements: '.trim($userInstructions);
        } else {
            $parts[] = 'Style: clean, modern, professional editorial illustration suitable for a featured article banner, 16:9 aspect ratio, vibrant yet balanced lighting, no distorted text or artifacts.';
        }

        return implode(' ', $parts);
    }

    /**
     * Generate an SEO-friendly filename from draft slug/keyword.
     */
    public function generateFilename(AiContentDraft $draft, string $extension = 'jpg'): string
    {
        $base = $draft->suggested_slug ?: Str::slug($draft->primary_keyword ?: $draft->topic);
        $cleanBase = Str::limit($base, 60, '');

        return "{$cleanBase}-featured.{$extension}";
    }

    /**
     * Generate SEO-optimized default alt text.
     */
    public function generateAltText(AiContentDraft $draft): string
    {
        $keyword = $draft->primary_keyword;
        $title = $draft->seo_title ?: $draft->topic;

        if ($keyword && stripos($title, $keyword) === false) {
            return "Featured image representing {$keyword} for {$title}";
        }

        return "Featured visual illustration for {$title}";
    }
}

