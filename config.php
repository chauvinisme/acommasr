<?php
/**
 * Configuration file for ACOM Shop
 * Edit these values according to your hosting environment
 */

return [
    'db' => [
        'host' => 'localhost',
        'name' => 'acom_db',
        'user' => 'acom_user',
        'pass' => 'acom_pass',
        'charset' => 'utf8mb4'
    ],
    'base_url' => 'https://example.com/acom',
    'uploads_dir' => __DIR__ . '/uploads',
    'uploads_url' => '/acom/uploads'
];
?>