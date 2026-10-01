<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Authz\Models\Policy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Models\Policy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-ea4411e0d803f3dbee2086bde470a1f32fade47e719e595ab36b5e2a5593ca73',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Models\\Policy',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Authz/Models/Policy.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Models',
    'name' => 'App\\Platform\\Authz\\Models\\Policy',
    'shortName' => 'Policy',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Policy record (SEC-021, K-009) on the central authorization group
 * (TEN-003 §2.1). tenant_id NULL marks a platform-default policy:
 * overridable but not silently removable (LD-15) — platform defaults are
 * readable by every tenant (IncludesPlatformDefaults) while mutations go
 * through platform tooling only.
 *
 * tenant_id is mass-assignable so kernel maintenance paths can write
 * explicit tenant scoping and platform-default rows (tenant_id NULL);
 * tenant admin surfaces never accept it — the policy grammar validator
 * rejects undeclared fields (strict validation, doc 06 §3.1).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string $effect
 * @property array<string, mixed> $capability_selector
 * @property list<array<string, mixed>>|null $scope_bindings
 * @property list<array<string, mixed>>|null $conditions
 * @property int $priority
 * @property string $status
 * @property int $version
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 40,
    'endLine' => 110,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
      0 => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
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
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 88,
            'startFilePos' => 1762,
            'endTokenPos' => 88,
            'endFilePos' => 1770,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
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
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'name\', \'description\', \'effect\', \'capability_selector\', \'scope_bindings\', \'conditions\', \'priority\', \'status\', \'version\']',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 52,
            'startTokenPos' => 97,
            'startFilePos' => 1800,
            'endTokenPos' => 129,
            'endFilePos' => 1956,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 6,
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
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 54,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'aliasName' => NULL,
      ),
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
 * @return Factory<Policy>
 */',
        'startLine' => 68,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'aliasName' => NULL,
      ),
      'includesPlatformDefaults' => 
      array (
        'name' => 'includesPlatformDefaults',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 73,
        'endLine' => 76,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'aliasName' => NULL,
      ),
      'isPlatformDefault' => 
      array (
        'name' => 'isPlatformDefault',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 78,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'aliasName' => NULL,
      ),
      'statusEnum' => 
      array (
        'name' => 'statusEnum',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'aliasName' => NULL,
      ),
      'toPayload' => 
      array (
        'name' => 'toPayload',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Full policy payload shared by the platform.policy.* events
 * (AC-T-0K01-01-3: events carry the full selector payload).
 *
 * @return array<string, mixed>
 */',
        'startLine' => 94,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\Policy',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\Policy',
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