<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\BelongsToTenant.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\BelongsToTenant
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-3684e2b1e65a7815ea69cd54525b3210fdc3d7d8fa33c97b1ef27fcfc03cac09',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\BelongsToTenant',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/BelongsToTenant.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy',
    'name' => 'App\\Platform\\Tenancy\\BelongsToTenant',
    'shortName' => 'BelongsToTenant',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * BelongsToTenant kernel trait (doc 02 §5.1, TEN-009).
 *
 * Applied to every tenant-scoped model:
 *  - creating: fills tenant_id from TenantContext; throws when the context
 *    is unresolved (fail-closed). An explicitly present tenant_id attribute
 *    (kernel maintenance / platform-default rows with NULL tenant) is
 *    respected; every such bypass site is architecture-tested.
 *  - global scope: TenantScope binds tenant_id from context on every read,
 *    update and delete (fail-closed when unresolved).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 39,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'bootBelongsToTenant' => 
      array (
        'name' => 'bootBelongsToTenant',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Tenancy',
        'declaringClassName' => 'App\\Platform\\Tenancy\\BelongsToTenant',
        'implementingClassName' => 'App\\Platform\\Tenancy\\BelongsToTenant',
        'currentClassName' => 'App\\Platform\\Tenancy\\BelongsToTenant',
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