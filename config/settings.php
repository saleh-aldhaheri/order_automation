<?php

return [
    'fields' => [
        'site_name' => [
            'type' => 'string',
            'default' => 'My App',
        ],

        'theme_mode' => [
            'type' => 'enum',
            'options' => ['light', 'dark', 'system'],
            'default' => 'light',
        ],

        'primary_color' => [
            'type' => 'string',
            'default' => '#2563eb',
        ],

        'secondary_color' => [
            'type' => 'string',
            'default' => '#1e293b',
        ],

        'font_size' => [
            'type' => 'enum',
            'options' => ['sm', 'md', 'lg'],
            'default' => 'md',
        ],

        'sidebar_enabled' => [
            'type' => 'boolean',
            'default' => true,
        ],

        'navbar_enabled' => [
            'type' => 'boolean',
            'default' => true,
        ],

        'logo' => [
            'type' => 'string',
            'default' => null,
            'nullable' => true,
        ],

        'favicon' => [
            'type' => 'string',
            'default' => null,
            'nullable' => true,
        ],
    ],
];
