@php
    $admin = auth('admin')->user();
    $baseClasses = 'flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500';
    $activeClasses = 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20';
    $inactiveClasses = 'text-slate-600 hover:bg-slate-100 hover:text-cyan-600 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-cyan-400';
@endphp

<nav class="mt-6 flex-1 space-y-6 text-xs" aria-label="Admin navigation">
    <div>
        <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Platform control</p>
        <div class="space-y-1">
            <a href="{{ route('admin.dashboard') }}" class="{{ $baseClasses }} {{ request()->routeIs('admin.dashboard') ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                <span>Mission Control</span>
            </a>
            @if($admin?->can('manage users'))
                <a href="{{ route('admin.users.index') }}" class="{{ $baseClasses }} {{ request()->routeIs('admin.users.*') ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>
                    <i data-lucide="users" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Registered Users</span>
                </a>
            @endif
            @if($admin?->can('manage affiliates'))
                <a href="{{ route('admin.affiliates.index') }}" class="{{ $baseClasses }} {{ request()->routeIs('admin.affiliates.*', 'admin.affiliate-applications.*') ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs('admin.affiliates.*', 'admin.affiliate-applications.*')) aria-current="page" @endif>
                    <i data-lucide="badge-dollar-sign" class="h-4 w-4" aria-hidden="true"></i>
                    <span>Affiliate Management</span>
                </a>
            @endif
            @if($admin?->can('manage ai models'))
                <a href="{{ route('admin.ai-configuration.index') }}" class="{{ $baseClasses }} {{ request()->routeIs('admin.ai-configuration.*') ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs('admin.ai-configuration.*')) aria-current="page" @endif>
                    <i data-lucide="cpu" class="h-4 w-4" aria-hidden="true"></i>
                    <span>AI Models &amp; Routing</span>
                </a>
            @endif
            @foreach([
                ['icon' => 'building-2', 'label' => 'Workspace Management'],
                ['icon' => 'server', 'label' => 'Job Queues & Latency'],
            ] as $futureItem)
                <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2 text-slate-400 opacity-60" aria-disabled="true" title="Not available in Phase 14A">
                    <i data-lucide="{{ $futureItem['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate">{{ $futureItem['label'] }}</span>
                    <span class="text-[8px] font-bold uppercase tracking-wider">Soon</span>
                </span>
            @endforeach
        </div>
    </div>

    <div>
        <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Content management</p>
        <div class="space-y-1">
            @foreach([
                ['permission' => 'manage homepage settings', 'route' => 'admin.homepage.index', 'icon' => 'house', 'label' => 'Homepage CMS'],
                ['permission' => 'manage about page', 'route' => 'admin.about.index', 'icon' => 'info', 'label' => 'About CMS'],
                ['permission' => 'manage feature page settings', 'route' => 'admin.feature_page.index', 'icon' => 'sparkles', 'label' => 'Features CMS'],
                ['permission' => 'manage blogs', 'route' => 'admin.blogs.index', 'icon' => 'notebook-tabs', 'label' => 'Blog Management'],
                ['permission' => 'manage blog page settings', 'route' => 'admin.blog_page.index', 'icon' => 'layout-template', 'label' => 'Blog Page CMS'],
                ['permission' => 'manage affiliate page settings', 'route' => 'admin.affiliate_page.index', 'icon' => 'handshake', 'label' => 'Affiliate Page CMS'],
            ] as $item)
                @if($admin?->can($item['permission']))
                    <a href="{{ route($item['route']) }}" class="{{ $baseClasses }} {{ request()->routeIs($item['route']) ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <div>
        <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Pricing and site</p>
        <div class="space-y-1">
            @foreach([
                ['permission' => 'manage pricing plans', 'route' => 'admin.pricing.index', 'icon' => 'layers-3', 'label' => 'Plans & Pricing Tiers'],
                ['permission' => 'manage pricing page settings', 'route' => 'admin.pricing_page.index', 'icon' => 'badge-dollar-sign', 'label' => 'Pricing Page CMS'],
                ['permission' => 'manage frontend navigation', 'route' => 'admin.frontend_navigation.index', 'icon' => 'panel-top', 'label' => 'Navigation & Footer'],
                ['permission' => 'manage site settings', 'route' => 'admin.settings.index', 'icon' => 'settings', 'label' => 'Site Settings'],
                ['permission' => 'manage seo settings', 'route' => 'admin.seo.index', 'icon' => 'search-check', 'label' => 'SEO Settings'],
            ] as $item)
                @if($admin?->can($item['permission']))
                    <a href="{{ route($item['route']) }}" class="{{ $baseClasses }} {{ request()->routeIs($item['route']) ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    @if($admin?->can('manage roles') || $admin?->can('create admin') || $admin?->can('edit admin') || $admin?->can('delete admin'))
        <div>
            <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Administration</p>
            <a href="{{ route('admin.role-permission.index') }}" class="{{ $baseClasses }} {{ request()->routeIs('admin.role-permission.*') ? $activeClasses : $inactiveClasses }}" @if(request()->routeIs('admin.role-permission.*')) aria-current="page" @endif>
                <i data-lucide="shield-cog" class="h-4 w-4" aria-hidden="true"></i>
                <span>Admins & Roles</span>
            </a>
        </div>
    @endif

    <div>
        <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Quick navigation</p>
        <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="{{ $baseClasses }} {{ $inactiveClasses }}">
            <i data-lucide="external-link" class="h-4 w-4" aria-hidden="true"></i>
            <span>View Public Website</span>
        </a>
    </div>
</nav>
