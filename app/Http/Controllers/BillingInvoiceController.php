<?php

namespace App\Http\Controllers;

use App\Models\BillingInvoice;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class BillingInvoiceController extends Controller
{
    public function __invoke(Workspace $workspace, BillingInvoice $billingInvoice): View
    {
        abort_unless($billingInvoice->workspace_id === $workspace->getKey(), 404);
        Gate::authorize('view', $billingInvoice);
        $billingInvoice->load(['items', 'payments']);

        return view('subscriptions.invoice', compact('workspace', 'billingInvoice'));
    }
}
