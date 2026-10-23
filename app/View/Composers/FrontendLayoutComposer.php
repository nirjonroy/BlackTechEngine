<?php

namespace App\View\Composers;

use App\Services\FrontendMetadata;
use App\Services\FrontendNavigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendLayoutComposer
{
    public function __construct(
        private readonly FrontendMetadata $metadata,
        private readonly FrontendNavigation $navigation,
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
            'frontendSeo' => $resolved['seo'],
            'frontendAnalytics' => $resolved['analytics'],
            'frontendNavigation' => $this->navigation->resolve(
                $resolved['siteSetting'],
                $this->request->route()?->getName(),
            ),
        ]);
    }
}
