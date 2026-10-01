<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Providers\PlatformServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Providers\PlatformServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-ee286989fef8932aadc1150e41b73c8da377c8ca540d0686342b9322030584e3',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Providers\\PlatformServiceProvider',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Providers/PlatformServiceProvider.php',
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
    'startLine' => 38,
    'endLine' => 108,
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
        'startLine' => 40,
        'endLine' => 58,
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
        'startLine' => 60,
        'endLine' => 75,
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
            'startLine' => 87,
            'endLine' => 87,
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
 * membership assignment) or a capability registry row — bumps the
 * owning tenant\'s decision generation, so cached decisions become
 * unreachable within the same request cycle in which the state
 * changed. Platform-default policy rows (tenant_id NULL) bump the
 * global generation. Bulk query-builder mutations that bypass model
 * events stay bounded by the cache TTL (belt-and-braces).
 */',
        'startLine' => 87,
        'endLine' => 107,
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