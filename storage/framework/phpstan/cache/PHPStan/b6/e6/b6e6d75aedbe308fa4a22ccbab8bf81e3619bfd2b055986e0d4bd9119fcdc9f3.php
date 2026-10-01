<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Console\TenantAdoptLegacyCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Console\TenantAdoptLegacyCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-7d3e1c209cdc21d980ab1e0ffa5d7bc2562975863058e7440e25ad1cd737bf5e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Console',
    'name' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
    'shortName' => 'TenantAdoptLegacyCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan tenant:adopt-legacy --owner= [--slug=] [--dry-run]`
 * (pre-era doctrine): adopt a single-tenant dataset into the multitenant
 * world. Provisions a `legacy` tenant, binds the owner, attributes every
 * NULL-tenant_id row to it, and verifies zero NULLs remain.
 *
 * Attribution, never fabrication: adopted rows point at the legacy tenant
 * and the command reports per-table counts, so operators can always tell
 * born-multitenant rows from adopted ones. Backed-out by restoring the
 * pre-freeze backup (there is no other copy — backup is mandatory and the
 * command refuses without --i-have-a-backup outside --dry-run).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 125,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant:adopt-legacy
        {--owner= : User id to bind as tenant owner}
        {--slug=legacy : Slug for the adopted tenant}
        {--dry-run : Report what would change without writing}
        {--i-have-a-backup : Confirm a pre-freeze backup exists}\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 30,
            'startTokenPos' => 57,
            'startFilePos' => 1002,
            'endTokenPos' => 57,
            'endFilePos' => 1257,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 30,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Adopt pre-tenancy single-tenant data into a legacy tenant\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 66,
            'startFilePos' => 1290,
            'endTokenPos' => 66,
            'endFilePos' => 1348,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'promotion' => 
          array (
            'name' => 'promotion',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'App\\Platform\\Tenancy\\TenantPromotion',
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
            'endColumn' => 53,
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
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 34,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Console',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
        'currentClassName' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
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