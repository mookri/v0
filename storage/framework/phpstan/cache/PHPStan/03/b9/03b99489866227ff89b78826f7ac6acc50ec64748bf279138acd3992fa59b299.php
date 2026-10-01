<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Tenancy\Exceptions\TenantContextMissingException.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Tenancy\Exceptions\TenantContextMissingException
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-d0b3aca53ad8e13230e1fe47cf45b0d915cb13ab6811c22a039c1d4ed8de891d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Tenancy/Exceptions/TenantContextMissingException.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
    'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
    'shortName' => 'TenantContextMissingException',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Thrown when tenant-scoped state is written or required outside a resolved
 * TenantContext (doc 02 §4 fail-closed, TEN-006/TEN-009). Callers MUST treat
 * this as a hard denial — never default to "all tenants".
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 21,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'operation' => 
          array (
            'name' => 'operation',
            'default' => 
            array (
              'code' => '\'tenant-scoped operation\'',
              'attributes' => 
              array (
                'startLine' => 14,
                'endLine' => 14,
                'startTokenPos' => 38,
                'startFilePos' => 420,
                'endTokenPos' => 38,
                'endFilePos' => 444,
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
            'startLine' => 14,
            'endLine' => 14,
            'startColumn' => 33,
            'endColumn' => 77,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
        'declaringClassName' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
        'implementingClassName' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
        'currentClassName' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
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