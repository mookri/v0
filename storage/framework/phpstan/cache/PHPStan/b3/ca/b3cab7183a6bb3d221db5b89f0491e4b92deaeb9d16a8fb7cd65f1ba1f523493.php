<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Tenancy\Models\Tenant.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Models\Tenant
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-bc40881452baef9064bf7020b46bda151a13811a58d569ac4eb4362aa3999617',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Tenancy/Models/Tenant.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Models',
    'name' => 'App\\Platform\\Tenancy\\Models\\Tenant',
    'shortName' => 'Tenant',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tenant registry record (central connection, TEN-005). Central tables
 * carry tenant_id as data, not as a row-level scope: isolation of tenant
 * business data is enforced by the resolution middleware (TEN-006) and the
 * BelongsToTenant scope on tenant tables (TEN-009).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $db_mode
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 * @property-read \\Illuminate\\Database\\Eloquent\\Collection<int, TenantMembership> $memberships
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 58,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'name' => 'STATE_ACTIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'active\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 71,
            'startFilePos' => 1112,
            'endTokenPos' => 71,
            'endFilePos' => 1119,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'STATE_SUSPENDED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'name' => 'STATE_SUSPENDED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'suspended\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 82,
            'startFilePos' => 1158,
            'endTokenPos' => 82,
            'endFilePos' => 1168,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 47,
      ),
      'STATE_DEPROVISIONED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'name' => 'STATE_DEPROVISIONED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'deprovisioned\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 93,
            'startFilePos' => 1211,
            'endTokenPos' => 93,
            'endFilePos' => 1225,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 55,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 102,
            'startFilePos' => 1257,
            'endTokenPos' => 102,
            'endFilePos' => 1265,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'db_mode\', \'state\']',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 111,
            'startFilePos' => 1295,
            'endTokenPos' => 122,
            'endFilePos' => 1330,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 63,
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
 * @return Factory<Tenant>
 */',
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Tenancy\\Models',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'currentClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'aliasName' => NULL,
      ),
      'memberships' => 
      array (
        'name' => 'memberships',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<TenantMembership, $this>
 */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Models',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
        'currentClassName' => 'App\\Platform\\Tenancy\\Models\\Tenant',
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