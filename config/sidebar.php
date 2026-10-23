<?php

return [
    [
        'title' => 'Dashboard',
        'route' => 'admin.dashboard',
        'type' => 'all',
    ],
    [
        'title' => 'Users List',
        'route' => '#',
        'type' => 'all',
    ],
    [
        'title' => 'Site Settings',
        'route' => 'admin.settings.index',
        'type' => 'all',
    ],
    [
        'title' => 'Homepage Setup',
        'route' => 'admin.homepage.index',
        'type' => 'permission',
        'key' => 'manage homepage settings',
    ],
    [
        'title' => 'Affiliate Page Setup',
        'route' => 'admin.affiliate_page.index',
        'type' => 'permission',
        'key' => 'manage affiliate page settings',
    ],
    [
        'title' => 'Header & Footer Setup',
        'route' => 'admin.frontend_navigation.index',
        'type' => 'permission',
        'key' => 'manage frontend navigation',
    ],
    [
        'title' => 'Pricing Page Setup',
        'route' => 'admin.pricing_page.index',
        'type' => 'all',
    ],
    [
        'title' => 'About Page Edit',
        'route' => 'admin.about.index',
        'type' => 'all',
    ],
    [
        'title' => 'SEO Settings',
        'route' => 'admin.seo.index',
        'type' => 'all',
    ],
    [
        'title' => 'Blogs',
        'route' => 'admin.blogs.index',
        'type' => 'all',
    ],
    [
        'title' => 'Pricing Plans',
        'route' => 'admin.pricing.index',
        'type' => 'all',
    ],
    [
        'title' => 'Features Page Setup',
        'route' => 'admin.feature_page.index',
        'type' => 'all',
    ],
    // Permission based menu
    [
        'title' => 'Roles & Permissions',
        'route' => 'admin.role-permission.index',
        'type' => 'permission',
        'key' => 'manage roles', // Je permission check korbe
    ],
    // Role based menu
    [
        'title' => 'Settings',
        'route' => '#',
        'type' => 'role',
        'key' => 'Super Admin', // Je role check korbe
    ],
];
