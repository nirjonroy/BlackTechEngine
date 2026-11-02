<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContentDraftRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentDraftExporter;
use App\Services\ContentEditorialWorkflow;
use Symfony\Component\HttpFoundation\Response;

class ContentDraftExportController extends Controller
{
    public function __construct(
        private readonly ContentEditorialWorkflow $workflow,
        private readonly ContentDraftExporter $exporter,
    ) {}

    public function __invoke(
        ExportContentDraftRequest $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): Response {
        $draft = $this->workflow->recordExport(
            $aiContentDraft,
            $request->user(),
            $request->expectedVersion(),
            $request->exportFormat(),
        );
        $draft->loadMissing('brand');
        $export = $this->exporter->export($draft, $request->exportFormat());

        return response($export['contents'], Response::HTTP_OK, [
            'Content-Type' => $export['mime'],
            'Content-Disposition' => 'attachment; filename="'.$export['filename'].'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
