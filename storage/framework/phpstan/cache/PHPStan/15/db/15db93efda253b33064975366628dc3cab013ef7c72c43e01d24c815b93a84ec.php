<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Contracts\ScopeBinding.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Contracts\ScopeBinding
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-205121e3ef7303b79faee7b3e9bc8da5abebabf3c1882e31a0f3c06da1ceffaa',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Contracts/ScopeBinding.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Contracts',
    'name' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
    'shortName' => 'ScopeBinding',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Policy / role-assignment scope binding (SEC-021, K-009 scope_bindings).
 * scope_type/scope_id resolve against hierarchy nodes (the hierarchy
 * wiring lands in T-0K02-01); scope_mode is the optional HIER-005
 * mechanism.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 78,
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
      'SCOPE_MODES' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'name' => 'SCOPE_MODES',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'SELF\', \'NODE\', \'DIRECT_CHILDREN\', \'SUBTREE\']',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 30,
            'startFilePos' => 406,
            'endTokenPos' => 41,
            'endFilePos' => 451,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 78,
      ),
    ),
    'immediateProperties' => 
    array (
      'scopeType' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'name' => 'scopeType',
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
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 9,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scopeId' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'name' => 'scopeId',
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
        'endColumn' => 39,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scopeMode' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'name' => 'scopeMode',
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
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 80,
            'startFilePos' => 617,
            'endTokenPos' => 80,
            'endFilePos' => 620,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 49,
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
          'scopeType' => 
          array (
            'name' => 'scopeType',
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
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 9,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scopeId' => 
          array (
            'name' => 'scopeId',
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
            'endColumn' => 39,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'scopeMode' => 
          array (
            'name' => 'scopeMode',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 20,
                'endLine' => 20,
                'startTokenPos' => 80,
                'startFilePos' => 617,
                'endTokenPos' => 80,
                'endFilePos' => 620,
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 49,
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
        'startLine' => 17,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Contracts',
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'currentClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'aliasName' => NULL,
      ),
      'fromArray' => 
      array (
        'name' => 'fromArray',
        'parameters' => 
        array (
          'binding' => 
          array (
            'name' => 'binding',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 38,
            'endColumn' => 51,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $binding
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 29,
        'endLine' => 60,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'App\\Platform\\Authz\\Contracts',
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'currentClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'aliasName' => NULL,
      ),
      'toArray' => 
      array (
        'name' => 'toArray',
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
 * @return array{scope_type: string, scope_id: string, scope_mode?: string}
 */',
        'startLine' => 65,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Contracts',
        'declaringClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'implementingClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        'currentClassName' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
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