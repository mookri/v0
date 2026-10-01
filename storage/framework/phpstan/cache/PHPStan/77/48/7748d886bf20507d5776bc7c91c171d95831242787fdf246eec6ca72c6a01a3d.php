<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Http\Controllers\TenantSelfServiceController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Http\Controllers\TenantSelfServiceController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-cb521873df1e62ae7cff647f928cda629d6ab355a6ce344ed90cbbe9eb86b686',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Http\\Controllers',
    'name' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
    'shortName' => 'TenantSelfServiceController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tenant self-service surface (TEN-006 §4.3, platform-global): the
 * authenticated user\'s own memberships with tenant summaries.
 *
 * Platform-global exemption: mounted WITHOUT the `tenant` middleware
 * (this endpoint is how clients discover which tenants they can enter).
 * Only active memberships resolve a context elsewhere; here all states
 * are listed so clients can explain denials. Never leaks other users\'
 * memberships: scoped to the authenticated principal, always.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 53,
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
      'index' => 
      array (
        'name' => 'index',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Http\\Request',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 27,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Http\\JsonResponse',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Http\\Controllers',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
        'currentClassName' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
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