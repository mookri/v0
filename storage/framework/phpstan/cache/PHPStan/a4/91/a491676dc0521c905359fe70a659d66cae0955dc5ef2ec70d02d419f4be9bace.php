<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Api\OpenApiComposer.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Api\OpenApiComposer
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-cbc5f664f2ba5a023d27c7e25e4499efb6405ea76bf2ab732cf5180adb1f566b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Api\\OpenApiComposer',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Api/OpenApiComposer.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Api',
    'name' => 'App\\Platform\\Api\\OpenApiComposer',
    'shortName' => 'OpenApiComposer',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * OpenAPI composition (api-platform §9.1): merges kernel shared components
 * with module fragments into one published document.
 *
 * Pure merge + validation over parsed arrays (filesystem stays in the
 * command): path collisions fail, duplicate operationIds fail, and every
 * x-permission code must exist in the declared set. Additive evolution is
 * a review rule on top of these mechanical gates.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 125,
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
      'PUBLIC_GATES' => 
      array (
        'declaringClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'implementingClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'name' => 'PUBLIC_GATES',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'ownership\', \'public\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 32,
            'startFilePos' => 904,
            'endTokenPos' => 37,
            'endFilePos' => 926,
          ),
        ),
        'docComment' => '/**
 * Gate taxonomy: every operation declares how it is guarded.
 * x-permission = capability middleware; x-gate ownership = resolved
 * actor must own the resource (checked in code); x-gate public =
 * credential-bearing public flow (invite accept) or auth-only
 * self-service. An operation with neither fails composition.
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 57,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'compose' => 
      array (
        'name' => 'compose',
        'parameters' => 
        array (
          'kernel' => 
          array (
            'name' => 'kernel',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 36,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fragments' => 
          array (
            'name' => 'fragments',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 51,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'declaredPermissions' => 
          array (
            'name' => 'declaredPermissions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 69,
            'endColumn' => 94,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
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
 * @param array<string, mixed> $kernel  Parsed kernel components file.
 * @param list<array{source: string, document: array<string, mixed>}> $fragments
 * @param list<string> $declaredPermissions
 * @return array{document: array<string, mixed>|null, errors: list<string>}
 */',
        'startLine' => 32,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Platform\\Api',
        'declaringClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'implementingClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'currentClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'aliasName' => NULL,
      ),
      'parseFile' => 
      array (
        'name' => 'parseFile',
        'parameters' => 
        array (
          'path' => 
          array (
            'name' => 'path',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 119,
            'endLine' => 119,
            'startColumn' => 38,
            'endColumn' => 49,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 119,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Platform\\Api',
        'declaringClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'implementingClassName' => 'App\\Platform\\Api\\OpenApiComposer',
        'currentClassName' => 'App\\Platform\\Api\\OpenApiComposer',
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