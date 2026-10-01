<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Exceptions\AuthorizationDeniedException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Exceptions\AuthorizationDeniedException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-572aaf220b88f9f9ffbe653957f29320328bcaaf079e48ea3dcc30c4e7441b3c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Exceptions/AuthorizationDeniedException.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Exceptions',
    'name' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
    'shortName' => 'AuthorizationDeniedException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Kernel authorization denial (doc 06 §4.2, SEC-022).
 *
 * Thrown by the kernel authorizer when a capability check fails. The error
 * envelope renders it as 403 with the stable machine code
 * `authorization_denied`; clients branch on the code, never the message.
 * Carries the denied capability + deciding reason so the denial audit
 * record and (from T-0K01-03) the decision log can be written.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 25,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Auth\\Access\\AuthorizationException',
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
      'capability' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'implementingClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'name' => 'capability',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 9,
        'endColumn' => 42,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reason' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'implementingClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'name' => 'reason',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '\'default_deny\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 52,
            'startFilePos' => 704,
            'endTokenPos' => 52,
            'endFilePos' => 717,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 55,
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
          'capability' => 
          array (
            'name' => 'capability',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 42,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'reason' => 
          array (
            'name' => 'reason',
            'default' => 
            array (
              'code' => '\'default_deny\'',
              'attributes' => 
              array (
                'startLine' => 20,
                'endLine' => 20,
                'startTokenPos' => 52,
                'startFilePos' => 704,
                'endTokenPos' => 52,
                'endFilePos' => 717,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 55,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'message' => 
          array (
            'name' => 'message',
            'default' => 
            array (
              'code' => '\'This action is unauthorized.\'',
              'attributes' => 
              array (
                'startLine' => 21,
                'endLine' => 21,
                'startTokenPos' => 61,
                'startFilePos' => 746,
                'endTokenPos' => 61,
                'endFilePos' => 775,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 56,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Exceptions',
        'declaringClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'implementingClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        'currentClassName' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
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