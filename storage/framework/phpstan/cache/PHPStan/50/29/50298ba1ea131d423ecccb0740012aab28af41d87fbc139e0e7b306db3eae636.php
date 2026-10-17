<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Providers\PlatformServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Providers\PlatformServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-4c1a08f9c7a0c4b6c24422a56e66a002a6421558f4cc69f06e60bc0744dc3fd0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Providers\\PlatformServiceProvider',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Providers/PlatformServiceProvider.php',
      ),
    ),
    'namespace' => 'App\\Providers',
    'name' => 'App\\Providers\\PlatformServiceProvider',
    'shortName' => 'PlatformServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Boots the platform kernel module system (doc 01 §2) and the Phase 0K
 * authorization services: the ambient TenantContext (singleton — the
 * scope, authorizer, audit writer and resolution middleware MUST share one
 * instance), the capability parser (SEC-020), the policy grammar
 * validator (SEC-021/LD-15), the audit writer (K-025), the capability
 * authorizer (SEC-022 floor) and the SEC-022 precedence-chain engine with
 * its generation-based decision cache (T-0K01-02).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 54,
    'endLine' => 151,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\ServiceProvider',
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
      'register' => 
      array (
        'name' => 'register',
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
        'startLine' => 56,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Providers',
        'declaringClassName' => 'App\\Providers\\PlatformServiceProvider',
        'implementingClassName' => 'App\\Providers\\PlatformServiceProvider',
        'currentClassName' => 'App\\Providers\\PlatformServiceProvider',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
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
        'startLine' => 88,
        'endLine' => 116,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Providers',
        'declaringClassName' => 'App\\Providers\\PlatformServiceProvider',
        'implementingClassName' => 'App\\Providers\\PlatformServiceProvider',
        'currentClassName' => 'App\\Providers\\PlatformServiceProvider',
        'aliasName' => NULL,
      ),
      'registerAuthzCacheInvalidation' => 
      array (
        'name' => 'registerAuthzCacheInvalidation',
        'parameters' => 
        array (
          'invalidator' => 
          array (
            'name' => 'invalidator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 129,
            'endLine' => 129,
            'startColumn' => 53,
            'endColumn' => 86,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Decision-cache invalidation hooks (AC-T-0K01-02-3): any mutation of
 * authorization state — a policy row, a grant row (role, permission,
 * membership assignment), a capability registry row, or an SoD rule
 * row — bumps the owning tenant\'s decision generation, so cached
 * decisions become unreachable within the same request cycle in
 * which the state changed. Platform-default rows (tenant_id NULL,
 * policies and SoD rules alike) bump the global generation. Bulk
 * query-builder mutations that bypass model events stay bounded by
 * the cache TTL (belt-and-braces).
 */',
        'startLine' => 129,
        'endLine' => 150,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Providers',
        'declaringClassName' => 'App\\Providers\\PlatformServiceProvider',
        'implementingClassName' => 'App\\Providers\\PlatformServiceProvider',
        'currentClassName' => 'App\\Providers\\PlatformServiceProvider',
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