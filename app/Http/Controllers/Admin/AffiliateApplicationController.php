<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AffiliateApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAffiliateApplicationIndexRequest;
use App\Models\AffiliateApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AffiliateApplicationController extends Controller
{
    public function index(AdminAffiliateApplicationIndexRequest $request): View
    {
        $filters = $request->filters();

        $applicationsQuery = AffiliateApplication::query()
            ->select([
                'id',
                'user_id',
                'status',
                'submitted_at',
                'reviewed_at',
                'reviewed_by_admin_id',
                'created_at',
            ])
            ->with([
                'user:id,name,email',
                'reviewedByAdmin:id,name,email',
                'affiliate:id,user_id,application_id,status',
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $applicationsQuery->where(function (Builder $query) use ($search): void {
                $query->whereHas('user', function (Builder $userQuery) use ($search): void {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    if (ctype_digit($search)) {
                        $userQuery->orWhereKey((int) $search);
                    }
                });

                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        }

        if ($filters['status'] !== 'all') {
            $applicationsQuery->where('status', $filters['status']);
        }

        match ($filters['sort']) {
            'oldest' => $applicationsQuery->orderBy('created_at')->orderBy('id'),
            'status' => $applicationsQuery->orderBy('status')->orderByDesc('id'),
            'submitted' => $applicationsQuery->orderByDesc('submitted_at')->orderByDesc('id'),
            'id' => $applicationsQuery->orderByDesc('id'),
            default => $applicationsQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $applications = $applicationsQuery
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.affiliate-applications.index', [
            'applicationStatuses' => AffiliateApplicationStatus::cases(),
            'applications' => $applications,
            'filters' => $filters,
        ]);
    }

    public function show(AffiliateApplication $affiliateApplication): View
    {
        $affiliateApplication->loadMissing([
            'user:id,name,email',
            'reviewedByAdmin:id,name,email',
            'affiliate:id,user_id,application_id,referral_code,status,approved_at,suspended_at,created_at',
        ]);

        return view('admin.affiliate-applications.show', compact('affiliateApplication'));
    }
}
