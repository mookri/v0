<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Console\TenantPromoteCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Console\TenantPromoteCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-dd03092fb19e21adb5f51074630a44ad704fe32c86e4056a640c633bfa98f106',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Console/TenantPromoteCommand.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Console',
    'name' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
    'shortName' => 'TenantPromoteCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * `php artisan tenant:promote --tenant= --target= [--dry-run]` (TEN-013):
 * freeze (state=promoting, writes rejected) → chunked backfill with
 * checksums → verify → cutover transaction → smoke. Dry-run copies and
 * verifies nothing; every other step is logged.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 70,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant:promote
        {--tenant= : Tenant id or slug}
        {--target= : Target connection name (must exist)}
        {--dry-run : Report scope and checksums without copying}\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 20,
            'startTokenPos' => 42,
            'startFilePos' => 522,
            'endTokenPos' => 42,
            'endFilePos' => 700,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 20,
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
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Promote a shared tenant toward a dedicated connection\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 51,
            'startFilePos' => 733,
            'endTokenPos' => 51,
            'endFilePos' => 787,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 85,
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
            'startLine' => 24,
            'endLine' => 24,
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
        'startLine' => 24,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Console',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
        'currentClassName' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
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