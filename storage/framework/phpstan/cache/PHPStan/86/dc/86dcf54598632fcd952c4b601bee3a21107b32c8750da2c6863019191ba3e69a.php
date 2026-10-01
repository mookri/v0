<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Platform\Modules\ModuleServiceProvider.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Modules\ModuleServiceProvider
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-82f9526e7933c529d54235eb29bf2d41eca4f87d6a8d4398ebfc8de22cd0df81',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'filename' => 'C:/Users/Victo/Downloads/webproj/navdhya-complete-backend/app/Platform/Modules/ModuleServiceProvider.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Modules',
    'name' => 'App\\Platform\\Modules\\ModuleServiceProvider',
    'shortName' => 'ModuleServiceProvider',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Base class every domain module extends (doc 01 §2-4).
 *
 * Modules override the template methods below; the kernel calls them in
 * dependency order during boot. Modules MUST NOT re-register kernel services
 * and MUST route all cross-module communication through contracts + events
 * (doc 01 §5).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 82,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Support\\ServiceProvider',
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
      'code' => 
      array (
        'name' => 'code',
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
        'docComment' => '/**
 * The module\'s manifest code; resolved from the class namespace by
 * default (App\\Modules\\Sales -> sales). Override to customize.
 */',
        'startLine' => 21,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'registerModule' => 
      array (
        'name' => 'registerModule',
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
 * Register module-scoped container bindings. Called during register().
 * Bind contract interfaces to implementations here (doc 01 §3.1).
 */',
        'startLine' => 32,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'bootModule' => 
      array (
        'name' => 'bootModule',
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
 * Boot module routes, migrations, policies, listeners and event
 * schemas. Called during boot() in dependency order.
 */',
        'startLine' => 40,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'register' => 
      array (
        'name' => 'register',
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
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
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
        'docComment' => NULL,
        'startLine' => 49,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'loadModuleMigrations' => 
      array (
        'name' => 'loadModuleMigrations',
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
 * Load this module\'s migrations from its Database/Migrations directory
 * (doc 01 §3.1, doc 02 §8: per-module migrations).
 */',
        'startLine' => 58,
        'endLine' => 65,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'registerModulePolicies' => 
      array (
        'name' => 'registerModulePolicies',
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
 * Register this module\'s Eloquent policies from Policies/ (doc 01 §3.3:
 * <Entity>Policy). M1 wires the Policy discovery gate.
 */',
        'startLine' => 71,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'aliasName' => NULL,
      ),
      'modulePath' => 
      array (
        'name' => 'modulePath',
        'parameters' => 
        array (
          'relative' => 
          array (
            'name' => 'relative',
            'default' => 
            array (
              'code' => '\'\'',
              'attributes' => 
              array (
                'startLine' => 75,
                'endLine' => 75,
                'startTokenPos' => 236,
                'startFilePos' => 2013,
                'endTokenPos' => 236,
                'endFilePos' => 2014,
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
            'startLine' => 75,
            'endLine' => 75,
            'startColumn' => 35,
            'endColumn' => 55,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
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
        'startLine' => 75,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Modules',
        'declaringClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'implementingClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        'currentClassName' => 'App\\Platform\\Modules\\ModuleServiceProvider',
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