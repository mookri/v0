<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Console\CleanupExpiredCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Console\CleanupExpiredCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-97916ec4f7466c3a382e6a45afbfceecf80173b2e30ebe52bfbe8b408916ce6c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Console/CleanupExpiredCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Console',
    'name' => 'App\\Platform\\Console\\CleanupExpiredCommand',
    'shortName' => 'CleanupExpiredCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan cleanup:expired [--dry-run] [--tenant=] [--force]`
 * Retention sweeper (single pass, per class):
 * - OTP challenges past expiry → delete (single-use secrets, no history value).
 * - Invites past expiry → mark EXPIRED (rows retained as audit history).
 * - Web sessions past expiry → delete.
 *
 * Kernel-maintenance bypass (TEN-009): global scopes are lifted explicitly
 * here and only here for sweep purposes; every swept row is tenant-scoped
 * in the report. Destructive runs require --force; dry-run is the default
 * when neither flag is given? No — explicitness wins: --dry-run reports,
 * --force executes, neither errors with usage.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 99,
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
        'declaringClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'implementingClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'cleanup:expired
        {--dry-run : Report what would change without writing}
        {--force : Execute deletions and state changes}
        {--tenant= : Scope the sweep to one tenant id}\'',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 27,
            'startTokenPos' => 42,
            'startFilePos' => 904,
            'endTokenPos' => 42,
            'endFilePos' => 1094,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 56,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'implementingClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Sweep expired OTP challenges, invites and sessions\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 51,
            'startFilePos' => 1127,
            'endTokenPos' => 51,
            'endFilePos' => 1178,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 82,
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
        'startLine' => 31,
        'endLine' => 98,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Console',
        'declaringClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'implementingClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
        'currentClassName' => 'App\\Platform\\Console\\CleanupExpiredCommand',
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