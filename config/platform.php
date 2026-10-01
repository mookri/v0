<?php

/*
 |--------------------------------------------------------------------------
 | Navdhya Platform Kernel Configuration
 |--------------------------------------------------------------------------
 |
 | Kernel-level settings shared by the module registry, API platform, and
 | tenancy resolution. See docs/01-architecture/module-system.md and
 | docs/06-tenancy/tenancy-and-data.md.
 |
 */

return [

    /*
     * Absolute path scanned by Platform\Modules\ModuleRegistry for
     * module manifests (app/Modules/<Module>/module.json).
     */
    'modules_path' => env('PLATFORM_MODULES_PATH', app_path('Modules')),

    /*
     * Public API version segment. All module API routes are mounted under
     * /api/{api_version}/{module}/... (doc 06 §2).
     */
    'api_version' => 'v1',

    /*
     * Authorization engine (T-0K01-02, SEC-022).
     */
    'authz' => [
        'decision_cache' => [
            // Belt-and-braces TTL bound (seconds) for cached chain
            // decisions; primary invalidation is generation-based and
            // immediate on any grant/policy mutation (AC-T-0K01-02-3).
            'ttl' => (int) env('PLATFORM_AUTHZ_DECISION_CACHE_TTL', 60),
        ],
    ],

    /*
     * Built-in module descriptor for the platform kernel itself. The kernel
     * has no module.json on disk (doc 05 §2.4); the registry synthesizes it
     * so dependency graphs and tooling can reference `platform`.
     */
    'kernel_module' => [
        'code' => 'platform',
        'name' => 'Platform Kernel',
        'version' => '1.0.0',
        'phase' => 0,
        'priority' => 0,

        // Module-specific action vocabulary extension (doc 01 §3.3): the
        // kernel declares `explain` for the restricted authorization
        // explain surface (T-0K01-02) and `manage` for lifecycle
        // administration (user suspend/activate).
        'actions' => ['explain', 'manage'],

        // Phase 0K kernel authorization surfaces (T-0K01-01, T-0K01-02).
        // The kernel has no on-disk module.json (doc 05 §2.4); its
        // manifest entries are declared here and materialized by
        // platform:sync-permissions.
        'permissions' => [
            ['code' => 'platform:policy:view', 'name' => 'View policies'],
            ['code' => 'platform:policy:configure', 'name' => 'Configure policies', 'sensitive' => true],
            ['code' => 'platform:authz:explain', 'name' => 'Explain authorization decisions', 'sensitive' => true],
            ['code' => 'platform:user:view', 'name' => 'View users'],
            ['code' => 'platform:user:manage', 'name' => 'Suspend and deactivate users', 'sensitive' => true],
        ],
        'scopes' => [],
        'events' => [
            ['name' => 'platform.policy.created', 'schema' => 'Authz/Events/schemas/policy_created.v1.json'],
            ['name' => 'platform.policy.updated', 'schema' => 'Authz/Events/schemas/policy_updated.v1.json'],
            ['name' => 'platform.policy.status_changed', 'schema' => 'Authz/Events/schemas/policy_status_changed.v1.json'],
        ],
        'navigation' => [
            ['group' => 'Platform', 'label' => 'Policies', 'route' => 'platform.policies.index', 'permission' => 'platform:policy:view'],
        ],
    ],
];
