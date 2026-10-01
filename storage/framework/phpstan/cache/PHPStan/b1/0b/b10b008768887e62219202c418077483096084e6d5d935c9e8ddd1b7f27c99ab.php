<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Authz\Http\Controllers\AuthzExplainController.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Http\Controllers\AuthzExplainController
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-294d65d33c43b432925de3d4beb459ee54240c32c12b10cd74b80a6d1c2cd620',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
    'name' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
    'shortName' => 'AuthzExplainController',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Authorization explain surface (T-0K01-02.3, SEC-022): restricted
 * endpoint returning the full precedence-chain trace for one capability
 * evaluation. Mounted behind auth + tenant resolution +
 * `platform:authz:explain` (routes/platform/api.php).
 *
 * Principals are "visible" when their membership belongs to the caller\'s
 * resolved tenant; anything else is denied with `authorization_denied`
 * (AC-T-0K01-02-4). Evaluation bypasses the decision cache so the trace
 * always reflects current authorization state. Deliberately does not
 * extend the app-shell Controller base class so the kernel layer keeps
 * its dependency boundary (doc 01 §7).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 28,
    'endLine' => 88,
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
      'ATTRIBUTE_FIELDS' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'name' => 'ATTRIBUTE_FIELDS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'amount\', \'currency\', \'org\', \'time\', \'status\']',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 70,
            'startFilePos' => 1166,
            'endTokenPos' => 84,
            'endFilePos' => 1212,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 85,
      ),
    ),
    'immediateProperties' => 
    array (
      'engine' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'name' => 'engine',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 55,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'principals' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'name' => 'principals',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Authz\\PrincipalResolver',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 54,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'context' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'name' => 'context',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Tenancy\\TenantContext',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 9,
        'endColumn' => 47,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'engine' => 
          array (
            'name' => 'engine',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'principals' => 
          array (
            'name' => 'principals',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\PrincipalResolver',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 54,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Tenancy\\TenantContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 9,
            'endColumn' => 47,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 32,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'currentClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'aliasName' => NULL,
      ),
      'explain' => 
      array (
        'name' => 'explain',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 29,
            'endColumn' => 44,
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
        'startLine' => 39,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
        'declaringClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'implementingClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        'currentClassName' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
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