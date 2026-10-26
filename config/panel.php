<?php

return [

    'domain' => env('PANEL_DOMAIN', 'invoix.co'),

    'image' => env('INVOIX_IMAGE', 'ghcr.io/sepineda1/invoix:latest'),

    'data_root' => env('TENANT_DATA_ROOT', '/var/lib/invoix/tenants'),

    'host_root' => env('TENANT_HOST_ROOT', env('TENANT_DATA_ROOT', '/var/lib/invoix/tenants')),

    'tenant_volume' => env('TENANT_VOLUME'),

    'network' => env('INVOIX_NETWORK', 'invoix'),

    'db_host' => env('DB_HOST', 'mysql'),

    'db_port' => env('DB_PORT', '3306'),

    'db_root_user' => env('MYSQL_ROOT_USER', 'root'),

    'db_root_password' => env('MYSQL_ROOT_PASSWORD', ''),

    'tenant_db_host' => env('TENANT_DB_HOST', 'mysql'),

    'tenant_db_port' => env('TENANT_DB_PORT', '3306'),

    'tenant_redis_host' => env('TENANT_REDIS_HOST', 'redis'),

    'caddy_container' => env('CADDY_CONTAINER', 'invoix-caddy'),

    'caddy_host' => env('CADDY_HOST', 'caddy'),

    'caddy_path' => env('CADDY_SITES_PATH', '/var/lib/invoix/caddy/tenants.caddy'),

    'caddy_tls' => env('CADDY_TLS', 'on_demand'),

    'webhook_secret' => env('PANEL_WEBHOOK_SECRET', ''),

    'health_timeout' => (int) env('TENANT_HEALTH_TIMEOUT', 120),

    'health_mode' => env('TENANT_HEALTH_MODE', 'public'),

];
