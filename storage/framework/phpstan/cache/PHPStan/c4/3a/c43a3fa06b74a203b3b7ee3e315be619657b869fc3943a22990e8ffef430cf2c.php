<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Identity\Http\Middleware\AuthenticateHmacToken.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Identity\Http\Middleware\AuthenticateHmacToken
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-6050b9e257e3a3f572c7ddbe374c3a72faa11847c44a5e8d4cd485b6495d1122',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Identity\\Http\\Middleware',
    'name' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
    'shortName' => 'AuthenticateHmacToken',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Session-or-HMAC authentication (token bridge): session clients pass
 * through untouched; otherwise frontend-minted bearer tokens are verified
 * (ActorTokenVerifier) and linked via external_id. Denies with the same
 * AuthenticationException as `auth` when neither succeeds, so routes use
 * this INSTEAD of `auth` (Laravel runs `auth` before custom aliases
 * regardless of listing order — stacking them breaks token clients).
 * Unknown external ids fail closed (no auto-provisioning).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 55,
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
      'handle' => 
      array (
        'name' => 'handle',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 28,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'next' => 
          array (
            'name' => 'next',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 46,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Symfony\\Component\\HttpFoundation\\Response',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Identity\\Http\\Middleware',
        'declaringClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'implementingClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'currentClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'aliasName' => NULL,
      ),
      'guard' => 
      array (
        'name' => 'guard',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Auth\\StatefulGuard',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 48,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Identity\\Http\\Middleware',
        'declaringClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'implementingClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
        'currentClassName' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
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