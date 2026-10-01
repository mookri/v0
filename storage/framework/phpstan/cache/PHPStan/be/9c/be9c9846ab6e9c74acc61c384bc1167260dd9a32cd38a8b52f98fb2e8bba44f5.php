<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Tenancy\Models\TenantMembership.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Models\TenantMembership
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-17ce26ac67635d462fcccfcdb4e1f9761c85b9db4e235f6b1b67d986ac748498',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Tenancy/Models/TenantMembership.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Models',
    'name' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
    'shortName' => 'TenantMembership',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * User<->tenant membership (central, K-002). State gates tenant entry
 * (TEN-006 fail-closed): only active memberships resolve a TenantContext.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $org_node_id
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 55,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
    ),
    'immediateConstants' => 
    array (
      'STATE_ACTIVE' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'name' => 'STATE_ACTIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'active\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 71,
            'startFilePos' => 934,
            'endTokenPos' => 71,
            'endFilePos' => 941,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'STATE_SUSPENDED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'name' => 'STATE_SUSPENDED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'suspended\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 82,
            'startFilePos' => 980,
            'endTokenPos' => 82,
            'endFilePos' => 990,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 47,
      ),
      'STATE_EXPIRED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'name' => 'STATE_EXPIRED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'expired\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 93,
            'startFilePos' => 1027,
            'endTokenPos' => 93,
            'endFilePos' => 1035,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 102,
            'startFilePos' => 1067,
            'endTokenPos' => 102,
            'endFilePos' => 1075,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'user_id\', \'org_node_id\', \'state\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 111,
            'startFilePos' => 1105,
            'endTokenPos' => 122,
            'endFilePos' => 1152,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 75,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'newFactory' => 
      array (
        'name' => 'newFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Factory<TenantMembership>
 */',
        'startLine' => 43,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Tenancy\\Models',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'currentClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'aliasName' => NULL,
      ),
      'tenant' => 
      array (
        'name' => 'tenant',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<Tenant, $this>
 */',
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Models',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'currentClassName' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));