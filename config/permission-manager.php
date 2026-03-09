<?php

declare(strict_types=1);

return [
    'route_prefix'       => 'permission-manager',
    'route_middleware'   => ['web', 'auth'],
    'access_gate'        => null,
    'guard'              => 'web',
    'user_model'         => \App\Models\User::class,
    'super_admin_role'   => 'super-admin',
    'enable_audit_log'   => true,
    'enable_groups'      => true,
    'pagination'         => 15,
    'user_display_field' => 'name',
    'user_search_fields' => ['name', 'email'],
];
