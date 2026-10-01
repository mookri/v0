<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\PolicyValidator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\PolicyValidator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-e43342ddcb883b8b677bf81cd2c4a1aacee6ea942028f15c65d76ce3be7a1d06',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\PolicyValidator',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/PolicyValidator.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz',
    'name' => 'App\\Platform\\Authz\\PolicyValidator',
    'shortName' => 'PolicyValidator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Policy payload validation (SEC-021, LD-15, T-0K01-01.3).
 *
 * Enforces the declarative policy grammar: effect allow|deny, a
 * module/resource/action capability selector (each segment concrete or
 * `*`), scope bindings, and the bounded condition grammar (closed operator
 * set over declared fields). Free-form expression languages are banned —
 * anything outside the grammar throws InvalidPolicyPayloadException, which
 * the API layer renders as 422 `validation_failed`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 223,
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
      'STORE_KEYS' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'name' => 'STORE_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'name\', \'description\', \'effect\', \'capability_selector\', \'scope_bindings\', \'conditions\', \'priority\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 26,
            'startTokenPos' => 50,
            'startFilePos' => 839,
            'endTokenPos' => 73,
            'endFilePos' => 961,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
      'UPDATE_KEYS' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'name' => 'UPDATE_KEYS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'name\', \'description\', \'effect\', \'capability_selector\', \'scope_bindings\', \'conditions\', \'priority\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 31,
            'startTokenPos' => 84,
            'startFilePos' => 997,
            'endTokenPos' => 107,
            'endFilePos' => 1119,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'validateForStore' => 
      array (
        'name' => 'validateForStore',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 41,
            'endLine' => 41,
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
 * Validate a create payload; returns the normalized, storable array.
 *
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 41,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'aliasName' => NULL,
      ),
      'validateForUpdate' => 
      array (
        'name' => 'validateForUpdate',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 39,
            'endColumn' => 50,
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
 * Validate a partial update payload (PATCH: absent = unchanged).
 *
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 62,
        'endLine' => 71,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'aliasName' => NULL,
      ),
      'assertKnownKeys' => 
      array (
        'name' => 'assertKnownKeys',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 38,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'allowed' => 
          array (
            'name' => 'allowed',
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 52,
            'endColumn' => 65,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $input
 * @param  list<string>  $allowed
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 79,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'aliasName' => NULL,
      ),
      'normalize' => 
      array (
        'name' => 'normalize',
        'parameters' => 
        array (
          'input' => 
          array (
            'name' => 'input',
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
            'startLine' => 97,
            'endLine' => 97,
            'startColumn' => 32,
            'endColumn' => 43,
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
 * @param  array<string, mixed>  $input
 * @return array<string, mixed>
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 97,
        'endLine' => 158,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'aliasName' => NULL,
      ),
      'bindingList' => 
      array (
        'name' => 'bindingList',
        'parameters' => 
        array (
          'bindings' => 
          array (
            'name' => 'bindings',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 34,
            'endColumn' => 48,
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
 * @return list<array<string, mixed>>
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 165,
        'endLine' => 190,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'aliasName' => NULL,
      ),
      'conditionList' => 
      array (
        'name' => 'conditionList',
        'parameters' => 
        array (
          'conditions' => 
          array (
            'name' => 'conditions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'mixed',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 197,
            'endLine' => 197,
            'startColumn' => 36,
            'endColumn' => 52,
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
 * @return list<array<string, mixed>>
 *
 * @throws InvalidPolicyPayloadException
 */',
        'startLine' => 197,
        'endLine' => 222,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz',
        'declaringClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'implementingClassName' => 'App\\Platform\\Authz\\PolicyValidator',
        'currentClassName' => 'App\\Platform\\Authz\\PolicyValidator',
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