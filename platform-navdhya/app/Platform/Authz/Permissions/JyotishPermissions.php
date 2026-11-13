<?php

namespace App\Platform\Authz\Permissions;

/**
 * Jyotish capability and scope constants (doc 01 §7, T-JY00-11).
 * Fail-closed vocabulary matching App\Modules\Jyotish\module.json.
 */
final readonly class JyotishPermissions
{
    // Declared capabilities
    public const CALCULATION_EXECUTE = 'jyotish:calculation:execute';
    public const CHART_READ = 'jyotish:chart:read';
    public const CHART_WRITE = 'jyotish:chart:write';
    public const RULESET_MANAGE = 'jyotish:ruleset:manage';
    public const PRACTITIONER_READ = 'jyotish:practitioner:read';

    // OAuth / JWT client scopes
    public const SCOPE_SELF = 'jyotish:self';
    public const SCOPE_READ = 'jyotish:read';
    public const SCOPE_WRITE = 'jyotish:write';

    /**
     * @var list<string>
     */
    public const ALL_PERMISSIONS = [
        self::CALCULATION_EXECUTE,
        self::CHART_READ,
        self::CHART_WRITE,
        self::RULESET_MANAGE,
        self::PRACTITIONER_READ,
    ];

    /**
     * @var list<string>
     */
    public const ALL_SCOPES = [
        self::SCOPE_SELF,
        self::SCOPE_READ,
        self::SCOPE_WRITE,
    ];
}

