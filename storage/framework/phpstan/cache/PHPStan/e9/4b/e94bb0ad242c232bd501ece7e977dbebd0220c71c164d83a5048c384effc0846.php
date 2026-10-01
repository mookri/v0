<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Observability\Health\ReadinessCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Observability\Health\ReadinessCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-746de56983e4b1b3abdd3c9e6c673d90627dd997c2707f42d59b5e6c1ec70914',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Observability/Health/ReadinessCheck.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Observability\\Health',
    'name' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
    'shortName' => 'ReadinessCheck',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `/ready` readiness probe (doc 05 §11.2, doc 06 §11).
 *
 * Checks (each degrades independently; any failure => 503, node leaves LB):
 *  - central DB ping
 *  - tenant shared DB ping
 *  - Redis/cache ping (production cache store)
 *  - queue connectivity probe
 *  - outbox relay heartbeat age < 120 s (M3 — stubbed until the outbox lands;
 *    the check is wired through a KernelHealth contract so M3 replaces the
 *    stub without touching this controller)
 *
 * `/up` (process alive, no dependencies) ships with the framework health
 * route in bootstrap/app.php.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
    'endLine' => 102,
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
      '__invoke' => 
      array (
        'name' => '__invoke',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 30,
            'endColumn' => 45,
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
        'startLine' => 27,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Observability\\Health',
        'declaringClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'implementingClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'currentClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'aliasName' => NULL,
      ),
      'ping' => 
      array (
        'name' => 'ping',
        'parameters' => 
        array (
          'connection' => 
          array (
            'name' => 'connection',
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
            'startLine' => 57,
            'endLine' => 57,
            'startColumn' => 27,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 57,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Observability\\Health',
        'declaringClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'implementingClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'currentClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'aliasName' => NULL,
      ),
      'pingCache' => 
      array (
        'name' => 'pingCache',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 65,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Observability\\Health',
        'declaringClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'implementingClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'currentClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'aliasName' => NULL,
      ),
      'pingQueue' => 
      array (
        'name' => 'pingQueue',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 77,
        'endLine' => 84,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Observability\\Health',
        'declaringClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'implementingClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'currentClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'aliasName' => NULL,
      ),
      'outboxRelayFresh' => 
      array (
        'name' => 'outboxRelayFresh',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Outbox relay heartbeat (doc 05 §11.2): relay writes a heartbeat row;
 * stale > 120 s means the relay is down. Until M3 lands the outbox,
 * this check reports ok when the outbox feature is not yet installed
 * (default-safe branch, doc 05 §10.2 rule 1).
 */',
        'startLine' => 92,
        'endLine' => 101,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Observability\\Health',
        'declaringClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'implementingClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
        'currentClassName' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
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