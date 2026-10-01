<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Console\TenantBackfillV2Command.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Console\TenantBackfillV2Command
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-909a6bbbac9598c75c7652cb438d3e06745374afaf28cfd1f014204b2647084d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Console/TenantBackfillV2Command.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Console',
    'name' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
    'shortName' => 'TenantBackfillV2Command',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan tenants:backfill-v2 [--dry-run]` — one-time data migration
 * for the TEN-005 amendment (C2): copies legacy `code`→`slug` and
 * `state`→`status` where the new columns are empty.
 *
 * Idempotent and auditable: reports examined/copied/skipped counts, writes
 * nothing under --dry-run, and aborts loudly on slug collisions instead of
 * overwriting.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 76,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenants:backfill-v2
        {--dry-run : Report what would change without writing}\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 20,
            'startTokenPos' => 37,
            'startFilePos' => 577,
            'endTokenPos' => 37,
            'endFilePos' => 660,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 64,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Backfill slug/status from legacy code/state (TEN-005 amendment)\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 46,
            'startFilePos' => 693,
            'endTokenPos' => 46,
            'endFilePos' => 757,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 95,
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
        'startLine' => 24,
        'endLine' => 75,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Console',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
        'currentClassName' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
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