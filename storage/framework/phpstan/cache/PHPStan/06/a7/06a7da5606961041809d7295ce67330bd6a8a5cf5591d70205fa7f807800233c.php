<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Console\DoctorCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Console\DoctorCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-4a052ac6713007ee5808bfa5d23470670bfef15cd262de9b55efaad46ac7a28e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Console\\DoctorCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Console/DoctorCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Console',
    'name' => 'App\\Platform\\Console\\DoctorCommand',
    'shortName' => 'DoctorCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan platform:doctor [--json]` — self-diagnosis for the platform
 * (F2). Fails fast on anything a deploy needs: PHP extensions, APP_KEY,
 * database reachability (central + tenant_shared), Redis reachability,
 * storage writability. Exit 0 = healthy, 1 = at least one check failed.
 * Every check reports ok/fail + detail; --json emits the machine-readable
 * form for pipelines and the future /ready parity check.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 120,
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
        'declaringClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'implementingClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'platform:doctor {--json : Emit machine-readable JSON}\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 37,
            'startFilePos' => 616,
            'endTokenPos' => 37,
            'endFilePos' => 670,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 83,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'implementingClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Check runtime, config, database, cache and storage health\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 46,
            'startFilePos' => 703,
            'endTokenPos' => 46,
            'endFilePos' => 761,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 89,
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
      'runChecks' => 
      array (
        'name' => 'runChecks',
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
 * @return list<array{check: string, ok: bool, detail: string}>
 */',
        'startLine' => 25,
        'endLine' => 99,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Console',
        'declaringClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'implementingClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'currentClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'aliasName' => NULL,
      ),
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
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
        'startLine' => 101,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Console',
        'declaringClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'implementingClassName' => 'App\\Platform\\Console\\DoctorCommand',
        'currentClassName' => 'App\\Platform\\Console\\DoctorCommand',
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