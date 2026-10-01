<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Http\Middleware\LimitJsonBody.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Http\Middleware\LimitJsonBody
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-85dbf6d9d42720c91d17712a112104a99533a510c8443a08a696bcd3f39b29fe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Http/Middleware/LimitJsonBody.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Http\\Middleware',
    'name' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
    'shortName' => 'LimitJsonBody',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * JSON body size cap (process gate, zero runtime risk): rejects JSON
 * request bodies over 1MB with 413 before routing work happens. File
 * uploads ride multipart (never JSON) and keep their own per-endpoint
 * caps; this guard covers accidental or abusive JSON payloads only.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 38,
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
      'MAX_BYTES' => 
      array (
        'declaringClassName' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
        'implementingClassName' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
        'name' => 'MAX_BYTES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1048576',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 40,
            'startFilePos' => 483,
            'endTokenPos' => 40,
            'endFilePos' => 489,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
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
            'startLine' => 19,
            'endLine' => 19,
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
            'startLine' => 19,
            'endLine' => 19,
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
        'startLine' => 19,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Http\\Middleware',
        'declaringClassName' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
        'implementingClassName' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
        'currentClassName' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
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