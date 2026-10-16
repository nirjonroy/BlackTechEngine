<?php

return [
    [
        'title' => 'Dashboard',
        'route' => 'admin.dashboard',
        'type'  => 'all',
    ],
    [
        'title' => 'Users List',
        'route' => '#',
        'type'  => 'all',
    ],
    [
        'title' => 'Site Settings',
        'route' => 'admin.settings.index',
        'type'  => 'all',
    ],
    [
        'title' => 'Pricing Page Setup',
        'route' => 'admin.pricing_page.index',
        'type'  => 'all',
    ],
    [
        'title' => 'About Page Edit',
        'route' => 'admin.about.index',
        'type'  => 'all',
    ],
    [
        'title' => 'SEO Settings',
        'route' => 'admin.seo.index',
        'type'  => 'all',
    ],
    [
        'title' => 'Blogs',
        'route' => 'admin.blogs.index',
        'type'  => 'all',
    ],
    [
        'title' => 'Pricing Plans',
        'route' => 'admin.pricing.index',
        'type'  => 'all',
    ],
    // Permission based menu
    [
        'title' => 'Roles & Permissions',
        'route' => 'admin.role-permission.index',
        'type'  => 'permission',
        'key'   => 'manage roles', // Je permission check korbe
    ],
    // Role based menu
    [
        'title' => 'Settings',
        'route' => '#', 
        'type'  => 'role',
        'key'   => 'Super Admin', // Je role check korbe
    ],
];