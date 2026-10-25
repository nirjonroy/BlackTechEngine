<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use Illuminate\View\View;

class AboutPageController extends Controller
{
    public function __invoke(): View
    {
        $about = AboutPage::query()
            ->with(['stats', 'comparisons', 'architectures', 'coreValues'])
            ->first();

        return view('frontend.about', [
            'about' => $about,
            'seoFallbackTitle' => $about?->hero_title ?: 'About BlackTechEngine',
            'seoFallbackDescription' => $about?->hero_description
                ?: 'Learn about BlackTechEngine and its approach to content workflows, search visibility, and generative engine optimization.',
            'primaryCtaUrl' => $this->safeHttpUrl($about?->cta_btn1_link),
            'secondaryCtaUrl' => $this->safeHttpUrl($about?->cta_btn2_link),
        ]);
    }

    private function safeHttpUrl(?string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url
            : null;
    }
}
