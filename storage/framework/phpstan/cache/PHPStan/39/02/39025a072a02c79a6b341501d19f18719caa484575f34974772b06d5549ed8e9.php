<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Catalog\Models\CatalogPrice.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Catalog\Models\CatalogPrice
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-5d48f74baebdfe471f675f098962515d3d4de4cc5dd3cce1d9b13d608f87c832',
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
 * @property int|null $pandit_id
 * @property int $price_minor
 * @property \\Illuminate\\Support\\Carbon $effective_from
 * @property \\Illuminate\\Support\\Carbon|null $effective_to
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 54,
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
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 52,
            'startFilePos' => 679,
            'endTokenPos' => 52,
            'endFilePos' => 693,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
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
          'code' => '[\'tenant_id\', \'sku\', \'market\', \'currency\', \'pandit_id\', \'price_minor\', \'effective_from\', \'effective_to\']',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 32,
            'startTokenPos' => 61,
            'startFilePos' => 723,
            'endTokenPos' => 87,
            'endFilePos' => 849,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 32,
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
        'startLine' => 34,
        'endLine' => 42,
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
        'startLine' => 44,
        'endLine' => 53,
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