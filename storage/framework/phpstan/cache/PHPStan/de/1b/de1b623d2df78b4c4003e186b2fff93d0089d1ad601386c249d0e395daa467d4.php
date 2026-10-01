<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Modules\ModuleRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Modules\ModuleRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-54387c8fe845b3e8c2e23ff0a750b8b1bce59e3b597ed52e47fdb037f8edaf2f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Modules\\ModuleRegistry',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Modules/ModuleRegistry.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Modules',
    'name' => 'App\\Platform\\Modules\\ModuleRegistry',
    'shortName' => 'ModuleRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Kernel module registry (doc 01 §2).
 *
 * Responsibilities:
 *  1. Discover modules by scanning app/Modules/{Module}/module.json.
 *  2. Validate manifests against doc 01 §3.2/§3.3 (fail fast, boot error).
 *  3. Resolve the dependency graph; cycles are a boot error.
 *  4. Expose query APIs consumed by the kernel and tooling: modules(),
 *     permissions(), scopes(), events(), enabled(tenant) — the latter is
 *     feature-flag gated and wired in M1 once FeatureFlags lands.
 *
 * The kernel itself has no on-disk manifest; it is synthesized from
 * config/platform.php (\'kernel_module\') so dependency graphs may reference
 * the \'platform\' module code (doc 05 §2.4).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 379,
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
      'modules' => 
      array (
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'name' => 'modules',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 32,
            'startFilePos' => 888,
            'endTokenPos' => 33,
            'endFilePos' => 889,
          ),
        ),
        'docComment' => '/**
 * @var list<ModuleManifest>
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'booted' => 
      array (
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'name' => 'booted',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 44,
            'startFilePos' => 920,
            'endTokenPos' => 44,
            'endFilePos' => 924,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 33,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'app' => 
      array (
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'name' => 'app',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Contracts\\Foundation\\Application',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 41,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validator' => 
      array (
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'name' => 'validator',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Modules\\ManifestValidator',
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
        'endColumn' => 53,
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
          'app' => 
          array (
            'name' => 'app',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Contracts\\Foundation\\Application',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 9,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'validator' => 
          array (
            'name' => 'validator',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Modules\\ManifestValidator',
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
            'endColumn' => 53,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'boot' => 
      array (
        'name' => 'boot',
        'parameters' => 
        array (
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
 * Discover, validate and topologically order all modules. Safe to call
 * repeatedly; discovery runs once per process.
 */',
        'startLine' => 41,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'registerModuleProviders' => 
      array (
        'name' => 'registerModuleProviders',
        'parameters' => 
        array (
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
 * Register every discovered module\'s service provider with Laravel, in
 * dependency order (migrations, bindings and boot hooks flow from
 * there). Manifests without a provider class are routes-only modules
 * and are skipped; ModuleShapeTest requires real modules to ship one.
 */',
        'startLine' => 70,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'modules' => 
      array (
        'name' => 'modules',
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
 * @return list<ModuleManifest> Dependency-ordered, kernel first.
 */',
        'startLine' => 87,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'find' => 
      array (
        'name' => 'find',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 26,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
                  'name' => 'App\\Platform\\Modules\\ModuleManifest',
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
        'docComment' => NULL,
        'startLine' => 94,
        'endLine' => 105,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'has' => 
      array (
        'name' => 'has',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
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
            'startLine' => 107,
            'endLine' => 107,
            'startColumn' => 25,
            'endColumn' => 36,
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
        'startLine' => 107,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'permissions' => 
      array (
        'name' => 'permissions',
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
 * All declared permissions across every module, keyed by code.
 *
 * @return array<string, array{code: string, name: string, sensitive: bool, module_code: string}>
 */',
        'startLine' => 117,
        'endLine' => 138,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'scopes' => 
      array (
        'name' => 'scopes',
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
 * All declared non-trivial scopes (doc 04 §6: read/write are implicit).
 *
 * @return list<string>
 */',
        'startLine' => 145,
        'endLine' => 160,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'events' => 
      array (
        'name' => 'events',
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
 * All declared event names across every module.
 *
 * @return list<string>
 */',
        'startLine' => 167,
        'endLine' => 182,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'enabled' => 
      array (
        'name' => 'enabled',
        'parameters' => 
        array (
          'module' => 
          array (
            'name' => 'module',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Modules\\ModuleManifest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 29,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'tenant' => 
          array (
            'name' => 'tenant',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 190,
                'endLine' => 190,
                'startTokenPos' => 817,
                'startFilePos' => 5279,
                'endTokenPos' => 817,
                'endFilePos' => 5282,
              ),
            ),
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
            'startLine' => 190,
            'endLine' => 190,
            'startColumn' => 53,
            'endColumn' => 72,
            'parameterIndex' => 1,
            'isOptional' => true,
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
        'docComment' => '/**
 * Feature-flag gate for per-tenant module enablement (doc 01 §2-5).
 * The FeatureFlags kernel service lands in M3; until then a module with
 * no feature_flag is always enabled and a flagged module defaults to
 * enabled (default-safe branch, doc 05 §10.2 rule 1).
 */',
        'startLine' => 190,
        'endLine' => 202,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'discover' => 
      array (
        'name' => 'discover',
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
 * @return list<ModuleManifest>
 */',
        'startLine' => 207,
        'endLine' => 251,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'kernelManifest' => 
      array (
        'name' => 'kernelManifest',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'App\\Platform\\Modules\\ModuleManifest',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 253,
        'endLine' => 273,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'assertNoDuplicateCodes' => 
      array (
        'name' => 'assertNoDuplicateCodes',
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
                'name' => 'App\\Platform\\Modules\\ModuleManifest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 45,
            'endColumn' => 66,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'discovered' => 
          array (
            'name' => 'discovered',
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
            'startLine' => 278,
            'endLine' => 278,
            'startColumn' => 69,
            'endColumn' => 85,
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
 * @param  list<ModuleManifest>  $discovered
 */',
        'startLine' => 278,
        'endLine' => 292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'assertDependenciesExist' => 
      array (
        'name' => 'assertDependenciesExist',
        'parameters' => 
        array (
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
        'docComment' => NULL,
        'startLine' => 294,
        'endLine' => 313,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'aliasName' => NULL,
      ),
      'resolveDependencyOrder' => 
      array (
        'name' => 'resolveDependencyOrder',
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
 * Kahn\'s algorithm for a stable topological order (dependencies first).
 * Cycles are a boot error (doc 01 §2-3).
 *
 * @return list<ModuleManifest>
 */',
        'startLine' => 321,
        'endLine' => 378,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleRegistry',
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