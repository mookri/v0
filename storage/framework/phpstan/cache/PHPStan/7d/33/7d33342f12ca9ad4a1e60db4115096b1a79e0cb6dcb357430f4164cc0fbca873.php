<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Models\CapabilityMetadata.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Models\CapabilityMetadata
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-416e7674ccff7aa62e16155bb0d0fb927e8709706b6faee01a43841703cb4010',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Models/CapabilityMetadata.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Models',
    'name' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
    'shortName' => 'CapabilityMetadata',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Capability registry row (SEC-020): the permission registry with
 * structured parse columns. Every <module>:<resource>:<action> code
 * resolves to module/resource/action plus version + sensitive flag.
 * Materialized per tenant by platform:sync-permissions (CON-005) — the
 * sync writes explicit tenant_id values (kernel maintenance path), hence
 * tenant_id is mass-assignable on this model only.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $module
 * @property string $resource
 * @property string $action
 * @property int $version
 * @property bool $sensitive
 * @property string|null $description
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 33,
    'endLine' => 62,
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
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 74,
            'startFilePos' => 1346,
            'endTokenPos' => 74,
            'endFilePos' => 1354,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
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
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'code\', \'name\', \'module\', \'resource\', \'action\', \'version\', \'sensitive\', \'description\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 45,
            'startTokenPos' => 83,
            'startFilePos' => 1384,
            'endTokenPos' => 112,
            'endFilePos' => 1506,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 45,
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
        'startLine' => 47,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
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
 * @return Factory<CapabilityMetadata>
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Authz\\Models',
        'declaringClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'implementingClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        'currentClassName' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
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