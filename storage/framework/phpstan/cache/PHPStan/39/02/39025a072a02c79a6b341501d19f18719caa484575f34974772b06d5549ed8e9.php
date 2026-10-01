<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Catalog\Models\CatalogPrice.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Catalog\Models\CatalogPrice
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-1c86f989b3dc0a4d4b01c0a4432b260c2b2d01709f5431889d08eed833bf7277',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Catalog/Models/CatalogPrice.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Catalog\\Models',
    'name' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
    'shortName' => 'CatalogPrice',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Effective-dated price row (minor units, never floats).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $market
 * @property string $currency
 * @property int $price_minor
 * @property \\Illuminate\\Support\\Carbon $effective_from
 * @property \\Illuminate\\Support\\Carbon|null $effective_to
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 52,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 52,
            'startFilePos' => 646,
            'endTokenPos' => 52,
            'endFilePos' => 660,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 44,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'sku\', \'market\', \'currency\', \'price_minor\', \'effective_from\', \'effective_to\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 31,
            'startTokenPos' => 61,
            'startFilePos' => 690,
            'endTokenPos' => 84,
            'endFilePos' => 803,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 6,
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
      'casts' => 
      array (
        'name' => 'casts',
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
        'docComment' => NULL,
        'startLine' => 33,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Modules\\Catalog\\Models',
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'currentClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'aliasName' => NULL,
      ),
      'getConnectionName' => 
      array (
        'name' => 'getConnectionName',
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
                  'name' => 'string',
                  'isIdentifier' => true,
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
        'startLine' => 42,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Catalog\\Models',
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        'currentClassName' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
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