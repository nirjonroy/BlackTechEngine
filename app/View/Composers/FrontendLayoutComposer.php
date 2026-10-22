<?php

namespace App\View\Composers;

use App\Services\FrontendMetadata;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendLayoutComposer
{
    public function __construct(
        private readonly FrontendMetadata $metadata,
        private readonly Request $request,
    ) {}

    public function compose(View $view): void
    {
        $viewData = $view->getData();
        $resolved = $this->metadata->resolve(
            $this->request->route()?->getName(),
            $viewData['seoFallbackTitle'] ?? null,
            $viewData['seoFallbackDescription'] ?? null,
            $viewData['pageSeo'] ?? [],
        );

        $view->with([
            'siteSetting' => $resolved['siteSetting'],
            'siteFacebookUrl' => $this->safeHttpUrl($resolved['siteSetting']?->facebook_url),
            'frontendSeo' => $resolved['seo'],
            'frontendAnalytics' => $resolved['analytics'],
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
