<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AffiliateApplicationStatus;
use App\Enums\AffiliateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAffiliateIndexRequest;
use App\Models\Affiliate;
use App\Models\AffiliateApplication;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class AffiliateController extends Controller
{
    public function index(AdminAffiliateIndexRequest $request): View
    {
        $filters = $request->filters();

        $affiliatesQuery = Affiliate::query()
            ->select([
                'id',
                'user_id',
                'application_id',
                'referral_code',
                'status',
                'approved_at',
                'suspended_at',
                'created_at',
            ])
            ->with([
                'user:id,name,email',
                'application:id,user_id,status',
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $affiliatesQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->whereHas('user', function (Builder $userQuery) use ($search): void {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");

                        if (ctype_digit($search)) {
                            $userQuery->orWhereKey((int) $search);
                        }
                    })
                    ->orWhere('referral_code', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        }

        if ($filters['status'] !== 'all') {
            $affiliatesQuery->where('status', $filters['status']);
        }

        match ($filters['sort']) {
            'oldest' => $affiliatesQuery->orderBy('created_at')->orderBy('id'),
            'status' => $affiliatesQuery->orderBy('status')->orderByDesc('id'),
            'id' => $affiliatesQuery->orderByDesc('id'),
            default => $affiliatesQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $affiliates = $affiliatesQuery
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.affiliates.index', [
            'activeAffiliates' => Affiliate::query()
                ->where('status', AffiliateStatus::Active->value)
                ->count(),
            'affiliates' => $affiliates,
            'affiliateStatuses' => AffiliateStatus::cases(),
            'filters' => $filters,
            'pendingApplications' => AffiliateApplication::query()
                ->where('status', AffiliateApplicationStatus::Pending->value)
                ->count(),
            'totalAffiliates' => Affiliate::query()->count(),
            'totalApplications' => AffiliateApplication::query()->count(),
        ]);
    }

    public function show(Affiliate $affiliate): View
    {
        $affiliate->loadMissing([
            'user:id,name,email',
            'application:id,user_id,status,submitted_at,reviewed_at,created_at',
        ]);

        return view('admin.affiliates.show', compact('affiliate'));
    }
}
