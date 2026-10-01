<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Events\PolicyUpdated.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Events\PolicyUpdated
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-f0d7ff5957a597b4aebd8c133192aa560955ac3db18fabaad762334372d1f2fb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Events/PolicyUpdated.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Events',
    'name' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
    'shortName' => 'PolicyUpdated',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Platform event `platform.policy.updated` (AC-T-0K01-01-3): emitted with
 * the full policy payload when a policy\'s content changes.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 35,
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
      'policy' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'implementingClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'name' => 'policy',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Authz\\Models\\Policy',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 9,
        'endColumn' => 38,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'actorId' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'implementingClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'name' => 'actorId',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'string',
                  'isIdentifier' => true,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 49,
            'startFilePos' => 371,
            'endTokenPos' => 49,
            'endFilePos' => 374,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
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
          'policy' => 
          array (
            'name' => 'policy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\Models\\Policy',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 9,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'actorId' => 
          array (
            'name' => 'actorId',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 15,
                'endLine' => 15,
                'startTokenPos' => 49,
                'startFilePos' => 371,
                'endTokenPos' => 49,
                'endFilePos' => 374,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 9,
            'endColumn' => 47,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 13,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Events',
        'declaringClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'implementingClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'currentClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'aliasName' => NULL,
      ),
      'platformEventName' => 
      array (
        'name' => 'platformEventName',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Events',
        'declaringClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'implementingClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'currentClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'aliasName' => NULL,
      ),
      'toPlatformPayload' => 
      array (
        'name' => 'toPlatformPayload',
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
        'docComment' => '/**
 * @return array<string, mixed>
 */',
        'startLine' => 27,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Events',
        'declaringClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'implementingClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        'currentClassName' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
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