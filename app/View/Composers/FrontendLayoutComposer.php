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
        );

        $view->with([
            'siteSetting' => $resolved['siteSetting'],
            'frontendSeo' => $resolved['seo'],
            'frontendAnalytics' => $resolved['analytics'],
        ]);
    }
}
