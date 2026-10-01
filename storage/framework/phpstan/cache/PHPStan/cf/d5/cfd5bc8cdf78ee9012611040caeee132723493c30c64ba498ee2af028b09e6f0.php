<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Authz\Console\SyncPermissionsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Authz\Console\SyncPermissionsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-fad807ee41706756a62fe6c8d18d9a15f9bdda166fc3fa82f996911453a25684',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Authz/Console/SyncPermissionsCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Authz\\Console',
    'name' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
    'shortName' => 'SyncPermissionsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan platform:sync-permissions` — materializes every
 * manifest-declared capability into the structured capability registry
 * (SEC-020, CON-005) and validates every stored policy selector
 * (SEC-021). Fail-closed: any malformed capability code, undeclared
 * action, or invalid policy selector aborts the sync with a named error
 * code BEFORE any write (AC-T-0K01-01-1). `--dry-run` prints the
 * per-tenant diff without touching the database.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 332,
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
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'platform:sync-permissions
        {--tenant= : Only sync the capability registry of this tenant}
        {--dry-run : Compute and print the diff without writing}\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 31,
            'startTokenPos' => 87,
            'startFilePos' => 1130,
            'endTokenPos' => 87,
            'endFilePos' => 1292,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 66,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Parse every manifest-declared capability into structured capability metadata and validate policy selectors (fail-closed)\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 96,
            'startFilePos' => 1325,
            'endTokenPos' => 96,
            'endFilePos' => 1446,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 152,
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
          'registry' => 
          array (
            'name' => 'registry',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Modules\\ModuleRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 28,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parser' => 
          array (
            'name' => 'parser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\CapabilityParser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 54,
            'endColumn' => 77,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 35,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'collectDeclaredCapabilities' => 
      array (
        'name' => 'collectDeclaredCapabilities',
        'parameters' => 
        array (
          'registry' => 
          array (
            'name' => 'registry',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Modules\\ModuleRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 50,
            'endColumn' => 73,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'parser' => 
          array (
            'name' => 'parser',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Authz\\CapabilityParser',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 76,
            'endColumn' => 99,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Parse + vocabulary-check every manifest-declared capability.
 *
 * @return array{0: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, 1: list<string>}
 */',
        'startLine' => 99,
        'endLine' => 153,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'validatePolicySelectors' => 
      array (
        'name' => 'validatePolicySelectors',
        'parameters' => 
        array (
          'registry' => 
          array (
            'name' => 'registry',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Modules\\ModuleRegistry',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 161,
            'endLine' => 161,
            'startColumn' => 46,
            'endColumn' => 69,
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
 * Every stored policy selector must be grammatically valid and its
 * concrete module segment must name a registered module.
 *
 * @return list<string>
 */',
        'startLine' => 161,
        'endLine' => 192,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'resolveTenants' => 
      array (
        'name' => 'resolveTenants',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'Illuminate\\Support\\Collection',
                  'isIdentifier' => false,
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
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return Collection<int, Tenant>|null
 */',
        'startLine' => 197,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'diffForTenant' => 
      array (
        'name' => 'diffForTenant',
        'parameters' => 
        array (
          'tenantId' => 
          array (
            'name' => 'tenantId',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 36,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'declared' => 
          array (
            'name' => 'declared',
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
            'startLine' => 220,
            'endLine' => 220,
            'startColumn' => 54,
            'endColumn' => 68,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>  $declared
 * @return array{add: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, update: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, remove: array<string, CapabilityMetadata>}
 */',
        'startLine' => 220,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'renderDiff' => 
      array (
        'name' => 'renderDiff',
        'parameters' => 
        array (
          'tenantCode' => 
          array (
            'name' => 'tenantCode',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 33,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'diff' => 
          array (
            'name' => 'diff',
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
            'startLine' => 268,
            'endLine' => 268,
            'startColumn' => 53,
            'endColumn' => 63,
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
 * @param  array{add: array<string, mixed>, update: array<string, mixed>, remove: array<string, CapabilityMetadata>}  $diff
 */',
        'startLine' => 268,
        'endLine' => 296,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'aliasName' => NULL,
      ),
      'applyDiff' => 
      array (
        'name' => 'applyDiff',
        'parameters' => 
        array (
          'tenantId' => 
          array (
            'name' => 'tenantId',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 32,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'diff' => 
          array (
            'name' => 'diff',
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
            'startLine' => 305,
            'endLine' => 305,
            'startColumn' => 50,
            'endColumn' => 60,
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
 * Kernel maintenance write path: rows carry an explicit tenant_id, so
 * the BelongsToTenant context-fill is bypassed by design (TEN-009
 * bypass site; covered by the isolation suite).
 *
 * @param  array{add: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, update: array<string, array{code: string, name: string, module: string, resource: string, action: string, sensitive: bool}>, remove: array<string, CapabilityMetadata>}  $diff
 */',
        'startLine' => 305,
        'endLine' => 331,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Authz\\Console',
        'declaringClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'implementingClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        'currentClassName' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
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