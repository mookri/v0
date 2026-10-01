<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Console\AuthzExplainCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Console\AuthzExplainCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-79c42253258d653e2bba3d3da6efc93bda3100106481c660a28c0c3e064f40a9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Console/AuthzExplainCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Console',
    'name' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
    'shortName' => 'AuthzExplainCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan platform:authz-explain` (T-0K01-02.4): debugging command
 * evaluating the SEC-022 chain for one membership + capability outside a
 * request context. The tenant context is resolved from the membership
 * itself and cleared afterwards (fail-closed on unknown memberships).
 * `--json` emits exactly the payload the GET /api/v1/authz/explain
 * endpoint returns under `data`.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 105,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'platform:authz-explain
        {membership : The tenant membership id to evaluate}
        {capability : The capability code to evaluate}
        {--amount= : Amount attribute for condition evaluation}
        {--currency= : Currency attribute (3-letter uppercase code)}
        {--org= : Org attribute}
        {--time= : Time attribute (parseable timestamp string)}
        {--status= : Status attribute}
        {--json : Emit only the machine-readable decision payload}\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 30,
            'startTokenPos' => 57,
            'startFilePos' => 779,
            'endTokenPos' => 57,
            'endFilePos' => 1253,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 68,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Explain the SEC-022 precedence-chain decision for a membership and capability\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 66,
            'startFilePos' => 1286,
            'endTokenPos' => 66,
            'endFilePos' => 1364,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 109,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'handle' => 
      array (
        'name' => 'handle',
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 28,
            'endColumn' => 57,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 60,
            'endColumn' => 88,
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
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 91,
            'endColumn' => 112,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'aliasName' => NULL,
      ),
      'attributes' => 
      array (
        'name' => 'attributes',
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
        'startLine' => 91,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
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