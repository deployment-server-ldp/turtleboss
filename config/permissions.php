<?php

/** Permission slugs grouped by admin module. Roles are mapped to these in RoleSeeder. */
return [
    'modules' => [
        'dashboard' => 'Dashboard',
        'products' => 'Products & Attributes',
        'categories' => 'Categories',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'inventory' => 'Inventory',
        'coupons' => 'Coupons & Discounts',
        'reviews' => 'Reviews',
        'content' => 'Homepage, Sliders, Menus, Pages, Blog, Media',
        'seo' => 'SEO & Redirects',
        'settings' => 'Shipping, Payment, Email, WhatsApp, General Settings',
        'users' => 'Users & Roles',
        'reports' => 'Reports & Analytics',
        'backup' => 'Backup & Maintenance',
    ],
    'roles' => [
        'super_admin' => ['name' => 'Super Admin', 'description' => 'Full access to everything', 'modules' => '*'],
        'store_manager' => ['name' => 'Store Manager', 'description' => 'Runs the store day to day', 'modules' => ['dashboard','products','categories','orders','customers','inventory','coupons','reviews','content','seo','settings','reports']],
        'product_manager' => ['name' => 'Product Manager', 'description' => 'Catalogue and stock', 'modules' => ['dashboard','products','categories','inventory','reviews']],
        'order_manager' => ['name' => 'Order Manager', 'description' => 'Orders, customers and coupons', 'modules' => ['dashboard','orders','customers','coupons','reports']],
        'content_manager' => ['name' => 'Content Manager', 'description' => 'Website content and SEO', 'modules' => ['dashboard','content','seo','reviews']],
    ],
];
