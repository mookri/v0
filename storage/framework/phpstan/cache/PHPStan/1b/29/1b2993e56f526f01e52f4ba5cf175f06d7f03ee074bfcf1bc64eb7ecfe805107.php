<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Console\CleanupExpiredCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Console\CleanupExpiredCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-869e3e19571f84f3c14ba63cfe4f94cd20cf52870c27a9c552bfb05e8d5d75c7',
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
 * - Step-up proofs past 24 h → delete (authority is the first 15 min).
 * - Customer OTP challenges past expiry → delete (module table, kernel rule).
 * - Actor sessions revoked or past expiry → delete (M5/TOKEN-001: a
 *   missing jti already fails closed in ActorTokenVerifier).
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
    'startLine' => 26,
    'endLine' => 143,
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
            'startLine' => 28,
            'endLine' => 31,
            'startTokenPos' => 42,
            'startFilePos' => 1192,
            'endTokenPos' => 42,
            'endFilePos' => 1382,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 31,
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
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 51,
            'startFilePos' => 1415,
            'endTokenPos' => 51,
            'endFilePos' => 1466,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
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
        'startLine' => 35,
        'endLine' => 142,
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