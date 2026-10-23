<?php

namespace App\Services;

use Illuminate\Support\Collection;

class FeaturePageContent
{
    /** @var array<string, string> */
    public const DESTINATION_OPTIONS = [
        'register' => 'Create account',
        'pricing' => 'Pricing page',
        'login' => 'Client sign in',
        'home' => 'Homepage',
        'about' => 'About page',
        'blog.index' => 'Blog',
        'affiliate' => 'Affiliate program',
    ];

    /** @var array<string, string> */
    public const ICON_OPTIONS = [
        'activity' => 'Activity',
        'badge-check' => 'Badge check',
        'bar-chart-3' => 'Bar chart',
        'brain-circuit' => 'Brain circuit',
        'calendar' => 'Calendar',
        'calendar-check' => 'Calendar check',
        'calendar-days' => 'Calendar days',
        'chart-no-axes-combined' => 'Trend chart',
        'check' => 'Check',
        'check-circle-2' => 'Check circle',
        'circle-gauge' => 'Gauge',
        'clock' => 'Clock',
        'cpu' => 'CPU',
        'database' => 'Database',
        'dna' => 'DNA',
        'file-check-2' => 'File check',
        'file-text' => 'File text',
        'globe-2' => 'Globe',
        'image' => 'Image',
        'layers' => 'Layers',
        'lightbulb' => 'Lightbulb',
        'link-2' => 'Link',
        'map-pin' => 'Map pin',
        'megaphone' => 'Megaphone',
        'messages-square' => 'Messages',
        'network' => 'Network',
        'radar' => 'Radar',
        'rocket' => 'Rocket',
        'search' => 'Search',
        'search-check' => 'Search check',
        'share-2' => 'Share',
        'shield' => 'Shield',
        'shield-check' => 'Shield check',
        'sliders' => 'Sliders',
        'sparkles' => 'Sparkles',
        'target' => 'Target',
        'wand-sparkles' => 'Magic wand',
        'workflow' => 'Workflow',
        'zap' => 'Lightning',
    ];

    /** @var list<string> */
    public const PRIMARY_IDS = [
        'brand_dna',
        'geo',
        'publishing_calendar',
        'social_drip',
        'local_seo',
        'gap_hunter',
    ];

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function features(?array $configuredFeatures): Collection
    {
        $configured = collect($configuredFeatures ?? [])->filter(fn (mixed $feature): bool => is_array($feature))->values();
        $identified = [];
        $legacy = [];
        $additional = [];

        foreach ($configured as $feature) {
            $id = $this->nonEmptyString($feature['id'] ?? null);

            if ($id !== null && in_array($id, self::PRIMARY_IDS, true) && ! array_key_exists($id, $identified)) {
                $identified[$id] = $feature;
            } elseif ($id === null) {
                $legacy[] = $feature;
            } elseif (preg_match('/\Aadditional-[a-z0-9-]+\z/', $id) === 1) {
                $additional[] = $feature;
            }
        }

        foreach (self::PRIMARY_IDS as $id) {
            if (! array_key_exists($id, $identified) && $legacy !== []) {
                $identified[$id] = array_shift($legacy);
            }
        }

        $additional = array_merge($legacy, $additional);

        $primary = collect($this->defaultFeatures())
            ->map(fn (array $default): array => $this->normalizeFeature($identified[$default['id']] ?? [], $default));

        $additionalFeatures = collect($additional)
            ->values()
            ->map(fn (array $feature, int $index): ?array => $this->normalizeAdditionalFeature($feature, $index))
            ->filter()
            ->sortBy('order')
            ->values();

        return $primary->concat($additionalFeatures)->values();
    }

    public function destinationUrl(?string $destination, string $fallback): string
    {
        $routeName = array_key_exists((string) $destination, self::DESTINATION_OPTIONS)
            ? $destination
            : $fallback;

        return route($routeName);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function defaultFeatures(): array
    {
        return [
            [
                'id' => 'brand_dna',
                'order' => 10,
                'icon' => 'dna',
                'title' => 'Proprietary Brand DNA & Voice Extraction Engine',
                'description' => 'Build a consistent voice profile from your existing content and apply it across future publishing workflows.',
                'supporting_points' => [
                    ['icon' => 'sliders', 'title' => 'Tone & Personality Calibration', 'description' => 'Tune formality, enthusiasm, analytical depth, and humor against approved brand guidance.'],
                    ['icon' => 'shield-check', 'title' => 'Banned Words Filter', 'description' => 'Exclude phrases and expressions that do not belong in your organization’s voice.'],
                    ['icon' => 'link-2', 'title' => 'Internal Linking Knowledge Graph', 'description' => 'Connect relevant topics to approved pages and resources with contextual anchor suggestions.'],
                ],
                'highlights' => [],
                'preview_heading' => 'Brand Voice Profile',
                'preview_label' => 'Illustrative interface preview',
                'preview_badge' => 'Ready',
                'preview_description' => 'A preview area can show how approved brand guidance affects generated drafts.',
                'preview_items' => [
                    ['icon' => 'activity', 'label' => 'Tone calibration', 'status' => 'Configured', 'description' => 'Approved tone guidance'],
                    ['icon' => 'bar-chart-3', 'label' => 'Technical depth', 'status' => 'Configured', 'description' => 'Approved depth guidance'],
                ],
                'footer_label' => null,
            ],
            [
                'id' => 'geo',
                'order' => 20,
                'icon' => 'cpu',
                'title' => 'Generative Engine Optimization (GEO 3.0)',
                'description' => 'Structure useful, citation-ready content for modern answer engines while retaining traditional search foundations.',
                'supporting_points' => [
                    ['icon' => 'check-circle-2', 'title' => 'Information-Gain Structure', 'description' => 'Organize original insights and supporting context for answer-engine retrieval.'],
                    ['icon' => 'check-circle-2', 'title' => 'Syntactic Direct Answers', 'description' => 'Shape concise question-and-answer passages for people and retrieval systems.'],
                    ['icon' => 'check-circle-2', 'title' => 'Schema & Entity Graphs', 'description' => 'Connect entities and page relationships with structured semantic context.'],
                ],
                'highlights' => [],
                'preview_heading' => 'Illustrative answer-engine preview',
                'preview_label' => null,
                'preview_badge' => 'Illustrative',
                'preview_description' => 'Structured content can provide concise answers, clear entities, and attributable supporting context.',
                'preview_items' => [
                    ['icon' => 'search-check', 'label' => 'Direct answer structure', 'status' => 'Structured', 'description' => 'Concise answer format with clear entity context.'],
                    ['icon' => 'link-2', 'label' => 'Citation-ready source context', 'status' => 'Sourced', 'description' => 'Supporting context prepared for attribution.'],
                ],
                'footer_label' => null,
            ],
            [
                'id' => 'publishing_calendar',
                'order' => 30,
                'icon' => 'calendar',
                'title' => 'Autonomous Content Calendar & Auto-Publisher',
                'description' => 'Organize a rolling publishing queue and coordinate approved content across supported publishing destinations.',
                'supporting_points' => [],
                'highlights' => [
                    ['label' => 'Rolling Queue', 'value' => 'Visible workflow', 'description' => 'Organize research, drafting, review, and publication in one visible workflow.'],
                    ['label' => 'Approval Controls', 'value' => 'Human review', 'description' => 'Keep editorial decisions with your team before content reaches a connected CMS.'],
                ],
                'preview_heading' => 'Illustrative Publishing Queue',
                'preview_label' => 'Workflow preview',
                'preview_badge' => 'Workflow Preview',
                'preview_description' => null,
                'preview_items' => [
                    ['icon' => 'badge-check', 'label' => 'Topic research approved', 'status' => 'Ready', 'description' => 'Research cleared for drafting.'],
                    ['icon' => 'file-check-2', 'label' => 'Draft awaiting editorial review', 'status' => 'Review', 'description' => 'A reviewer still controls publication.'],
                    ['icon' => 'calendar-check', 'label' => 'CMS publication scheduled', 'status' => 'Scheduled', 'description' => 'An approved publication slot.'],
                ],
                'footer_label' => null,
            ],
            [
                'id' => 'social_drip',
                'order' => 40,
                'icon' => 'share-2',
                'title' => 'Omnichannel Social Drip Engine',
                'description' => 'Repurpose approved articles into channel-specific formats for consistent distribution across social platforms.',
                'supporting_points' => [
                    ['icon' => 'share-2', 'title' => 'Format Repurposing', 'description' => null],
                    ['icon' => 'target', 'title' => 'Hashtag Clustering', 'description' => null],
                    ['icon' => 'calendar-check', 'title' => 'Schedule Planning', 'description' => null],
                    ['icon' => 'clock', 'title' => 'Drip Delays', 'description' => null],
                ],
                'highlights' => [],
                'preview_heading' => 'One approved article, multiple formats',
                'preview_label' => 'Channel-specific previews',
                'preview_badge' => 'Illustrative',
                'preview_description' => 'Channel-specific copy prepared for review and scheduling.',
                'preview_items' => [
                    ['icon' => 'network', 'label' => 'LinkedIn', 'status' => 'Prepared', 'description' => 'Professional social copy prepared for review.'],
                    ['icon' => 'messages-square', 'label' => 'X Thread', 'status' => 'Prepared', 'description' => 'A concise thread format prepared for review.'],
                    ['icon' => 'megaphone', 'label' => 'Facebook', 'status' => 'Prepared', 'description' => 'Conversational social copy prepared for review.'],
                    ['icon' => 'image', 'label' => 'Instagram', 'status' => 'Prepared', 'description' => 'A caption format prepared for review.'],
                ],
                'footer_label' => null,
            ],
            [
                'id' => 'local_seo',
                'order' => 50,
                'icon' => 'map-pin',
                'title' => 'Local SEO & Google Business Autopilot',
                'description' => 'Support local publishing and reputation workflows designed for location-focused businesses and agencies.',
                'supporting_points' => [
                    ['icon' => 'check', 'title' => 'Google Business publishing workflows', 'description' => null],
                    ['icon' => 'check', 'title' => 'Review response drafting with tone guidance', 'description' => null],
                    ['icon' => 'check', 'title' => 'Local citation consistency checks', 'description' => null],
                ],
                'highlights' => [],
                'preview_heading' => null,
                'preview_label' => null,
                'preview_badge' => null,
                'preview_description' => null,
                'preview_items' => [],
                'footer_label' => 'Platform capability',
            ],
            [
                'id' => 'gap_hunter',
                'order' => 60,
                'icon' => 'search',
                'title' => 'Continuous Semantic Keyword & Gap Hunter',
                'description' => 'Monitor topic opportunities and organize content ideas around relevant search intent and competitive gaps.',
                'supporting_points' => [
                    ['icon' => 'check', 'title' => 'Competitor topic monitoring', 'description' => null],
                    ['icon' => 'check', 'title' => 'Search-intent opportunity clustering', 'description' => null],
                    ['icon' => 'check', 'title' => 'Seasonal topic planning', 'description' => null],
                ],
                'highlights' => [],
                'preview_heading' => null,
                'preview_label' => null,
                'preview_badge' => null,
                'preview_description' => null,
                'preview_items' => [],
                'footer_label' => 'Platform capability',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $feature
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    private function normalizeFeature(array $feature, array $default): array
    {
        return [
            'id' => $default['id'],
            'order' => $default['order'],
            'icon' => $this->safeIcon($feature['icon'] ?? null, $default['icon']),
            'title' => $this->nonEmptyString($feature['title'] ?? null) ?? $default['title'],
            'description' => $this->nonEmptyString($feature['description'] ?? null) ?? $default['description'],
            'supporting_points' => $this->nestedItems($feature, 'supporting_points', $default['supporting_points'], 'title'),
            'highlights' => $this->nestedItems($feature, 'highlights', $default['highlights'], 'label'),
            'preview_heading' => $this->nullableSetting($feature, 'preview_heading', $default['preview_heading']),
            'preview_label' => $this->nullableSetting($feature, 'preview_label', $default['preview_label']),
            'preview_badge' => $this->nullableSetting($feature, 'preview_badge', $default['preview_badge']),
            'preview_description' => $this->nullableSetting($feature, 'preview_description', $default['preview_description']),
            'preview_items' => $this->nestedItems($feature, 'preview_items', $default['preview_items'], 'label'),
            'footer_label' => $this->nullableSetting($feature, 'footer_label', $default['footer_label']),
        ];
    }

    /**
     * @param  array<string, mixed>  $feature
     * @return array<string, mixed>|null
     */
    private function normalizeAdditionalFeature(array $feature, int $index): ?array
    {
        $title = $this->nonEmptyString($feature['title'] ?? null);
        $description = $this->nonEmptyString($feature['description'] ?? null);

        if ($title === null || $description === null) {
            return null;
        }

        $id = $this->nonEmptyString($feature['id'] ?? null);

        return [
            'id' => $id !== null && preg_match('/\Aadditional-[a-z0-9-]+\z/', $id) === 1 ? $id : 'additional-'.($index + 1),
            'order' => is_numeric($feature['order'] ?? null) ? (int) $feature['order'] : 100 + $index,
            'icon' => $this->safeIcon($feature['icon'] ?? null),
            'title' => $title,
            'description' => $description,
            'supporting_points' => $this->nestedItems($feature, 'supporting_points', [], 'title'),
            'highlights' => [],
            'preview_heading' => null,
            'preview_label' => null,
            'preview_badge' => null,
            'preview_description' => null,
            'preview_items' => [],
            'footer_label' => $this->nonEmptyString($feature['footer_label'] ?? null) ?? 'Platform capability',
        ];
    }

    /**
     * @param  array<string, mixed>  $feature
     * @param  array<int, array<string, mixed>>  $defaults
     * @return array<int, array<string, mixed>>
     */
    private function nestedItems(array $feature, string $key, array $defaults, string $requiredKey): array
    {
        if (! array_key_exists($key, $feature)) {
            return $defaults;
        }

        if (! is_array($feature[$key])) {
            return [];
        }

        return collect($feature[$key])
            ->filter(fn (mixed $item): bool => is_array($item) && $this->nonEmptyString($item[$requiredKey] ?? null) !== null)
            ->map(fn (array $item): array => [
                'icon' => $this->safeIcon($item['icon'] ?? null, 'check'),
                'title' => $this->nonEmptyString($item['title'] ?? null),
                'label' => $this->nonEmptyString($item['label'] ?? null),
                'value' => $this->nonEmptyString($item['value'] ?? null),
                'status' => $this->nonEmptyString($item['status'] ?? null),
                'description' => $this->nonEmptyString($item['description'] ?? null),
            ])
            ->values()
            ->all();
    }

    private function nullableSetting(array $feature, string $key, mixed $default): ?string
    {
        return array_key_exists($key, $feature)
            ? $this->nonEmptyString($feature[$key])
            : $this->nonEmptyString($default);
    }

    private function safeIcon(mixed $icon, string $fallback = 'sparkles'): string
    {
        return is_string($icon) && array_key_exists($icon, self::ICON_OPTIONS)
            ? $icon
            : $fallback;
    }

    private function nonEmptyString(mixed $value): ?string
    {
        return is_string($value) && filled(trim($value)) ? $value : null;
    }
}
