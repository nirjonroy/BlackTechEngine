<?php

namespace App\Models;

use Database\Factories\AffiliatePageSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliatePageSetting extends Model
{
    /** @use HasFactory<AffiliatePageSettingFactory> */
    use HasFactory;

    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'program-status' => 'Program status section',
        'register' => 'Standard account registration',
        'login' => 'Log in',
        'features' => 'Features page',
        'pricing' => 'Pricing page',
        'about' => 'About page',
        'blog.index' => 'Blog',
        'home' => 'Homepage',
    ];

    /** @var array<string, string> */
    public const INFORMATIONAL_DESTINATION_OPTIONS = [
        'program-status' => 'Program status section',
        'features' => 'Features page',
        'pricing' => 'Pricing page',
        'about' => 'About page',
        'blog.index' => 'Blog',
        'home' => 'Homepage',
    ];

    /** @var array<string, string> */
    public const AVAILABILITY_OPTIONS = [
        'not_open' => 'Not Open Yet',
        'planning' => 'In Planning',
        'pending_approval' => 'Pending Approval',
    ];

    /** @var array<string, string> */
    public const RESOURCE_AVAILABILITY_OPTIONS = [
        'not_available' => 'Not Available Yet',
        'planned' => 'Planned',
        'pending_approval' => 'Pending Approval',
    ];

    /** @var list<string> */
    public const WORKFLOW_ICONS = [
        'bell-ring',
        'clipboard-check',
        'clock',
        'file-check-2',
        'link-2',
        'megaphone',
        'shield-check',
        'sparkles',
        'users',
    ];

    /** @var list<array{icon: string, title: string, description: string}> */
    public const DEFAULT_WORKFLOW_STEPS = [
        [
            'icon' => 'bell-ring',
            'title' => 'Wait for the Launch Announcement',
            'description' => 'Program availability and approved commercial terms will be published before applications are accepted.',
        ],
        [
            'icon' => 'clipboard-check',
            'title' => 'Apply Through a Dedicated Flow',
            'description' => 'A future application will be separate from standard account registration and will require an explicit review.',
        ],
        [
            'icon' => 'link-2',
            'title' => 'Receive Approved Partner Access',
            'description' => 'Approved partners would receive tracking tools and materials only after the required backend systems exist.',
        ],
    ];

    protected $fillable = [
        'hero_badge',
        'hero_title',
        'hero_highlighted_title',
        'hero_description',
        'primary_cta_label',
        'primary_cta_destination',
        'secondary_cta_label',
        'secondary_cta_destination',
        'hero_disclosure',
        'status_heading',
        'status_description',
        'terms_heading',
        'availability_status',
        'availability_description',
        'workflow_heading',
        'workflow_description',
        'workflow_steps',
        'resources_badge',
        'resources_heading',
        'resources_description',
        'resources_availability_status',
        'final_cta_label',
        'final_cta_destination',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'workflow_steps' => 'array',
        ];
    }
}
