<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAffiliatePageSettingRequest;
use App\Models\AffiliatePageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AffiliatePageSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.affiliate_page_settings.index', [
            'setting' => AffiliatePageSetting::query()->first() ?? new AffiliatePageSetting,
            'destinationOptions' => AffiliatePageSetting::DESTINATION_OPTIONS,
            'availabilityOptions' => AffiliatePageSetting::AVAILABILITY_OPTIONS,
            'resourceAvailabilityOptions' => AffiliatePageSetting::RESOURCE_AVAILABILITY_OPTIONS,
            'informationalDestinationOptions' => AffiliatePageSetting::INFORMATIONAL_DESTINATION_OPTIONS,
            'workflowIcons' => AffiliatePageSetting::WORKFLOW_ICONS,
            'defaultWorkflowSteps' => AffiliatePageSetting::DEFAULT_WORKFLOW_STEPS,
        ]);
    }

    public function update(UpdateAffiliatePageSettingRequest $request): RedirectResponse
    {
        $setting = AffiliatePageSetting::query()->first() ?? new AffiliatePageSetting;
        $setting->fill($request->validated())->save();

        return back()->with('success', 'Affiliate page settings updated successfully!');
    }
}
