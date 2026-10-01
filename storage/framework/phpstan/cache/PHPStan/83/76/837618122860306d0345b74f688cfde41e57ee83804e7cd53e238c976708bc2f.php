<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Models\TenantMembership.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Models\TenantMembership
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-c7d10ca322ff0489ad1ccdd307c657a45eb3efcbdb7ca1bbcf417aaf64866db3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Models/TenantMembership.php',
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
    'startLine' => 25,
    'endLine' => 64,
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
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 76,
            'startFilePos' => 955,
            'endTokenPos' => 76,
            'endFilePos' => 962,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
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
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 87,
            'startFilePos' => 1001,
            'endTokenPos' => 87,
            'endFilePos' => 1011,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
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
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 98,
            'startFilePos' => 1048,
            'endTokenPos' => 98,
            'endFilePos' => 1056,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
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
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 107,
            'startFilePos' => 1088,
            'endTokenPos' => 107,
            'endFilePos' => 1096,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
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
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 116,
            'startFilePos' => 1126,
            'endTokenPos' => 127,
            'endFilePos' => 1173,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
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
        'startLine' => 44,
        'endLine' => 47,
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
        'startLine' => 52,
        'endLine' => 55,
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
      'user' => 
      array (
        'name' => 'user',
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
 * @return BelongsTo<User, $this>
 */',
        'startLine' => 60,
        'endLine' => 63,
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