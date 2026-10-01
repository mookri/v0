<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Models\RoleScopeBinding.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Models\RoleScopeBinding
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-c8d5c3c351dab453a2abd78f8dd08331a36c7953a2f9912557cd0e8f39004ba2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Models/RoleScopeBinding.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Models',
    'name' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
    'shortName' => 'RoleScopeBinding',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * SEC-021 role-assignment scoping: binds a role assignment to a scope
 * (scope_type/scope_id resolved against hierarchy nodes; the hierarchy
 * wiring lands in T-0K02-01) with an optional HIER-005 scope mode.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $role_assignment_id
 * @property string $scope_type
 * @property string $scope_id
 * @property string|null $scope_mode
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 53,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 79,
            'startFilePos' => 1108,
            'endTokenPos' => 79,
            'endFilePos' => 1116,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
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
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'role_assignment_id\', \'scope_type\', \'scope_id\', \'scope_mode\']',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 88,
            'startFilePos' => 1146,
            'endTokenPos' => 99,
            'endFilePos' => 1207,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 89,
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
 * @return Factory<RoleScopeBinding>
 */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'aliasName' => NULL,
      ),
      'assignment' => 
      array (
        'name' => 'assignment',
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
 * @return BelongsTo<MembershipRoleAssignment, $this>
 */',
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
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