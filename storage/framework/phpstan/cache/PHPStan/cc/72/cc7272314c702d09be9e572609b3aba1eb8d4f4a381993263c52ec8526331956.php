<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Catalog\Models\CatalogItem.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Catalog\Models\CatalogItem
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-83f88bf7e4b42f877839953dc4ed9b2109097aaa1d7c4147b71b02ca0ea71fa2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Catalog/Models/CatalogItem.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Catalog\\Models',
    'name' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
    'shortName' => 'CatalogItem',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Catalog item (tenant-scoped sellable).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string|null $vendor_id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string $currency
 * @property int $price_minor
 * @property array<string, mixed>|null $attributes
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 70,
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
      2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
      'STATUS_DRAFT' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'name' => 'STATUS_DRAFT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'DRAFT\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 66,
            'startFilePos' => 811,
            'endTokenPos' => 66,
            'endFilePos' => 817,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'STATUS_ACTIVE' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'name' => 'STATUS_ACTIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ACTIVE\'',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 33,
            'startTokenPos' => 77,
            'startFilePos' => 854,
            'endTokenPos' => 77,
            'endFilePos' => 861,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'STATUS_ARCHIVED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'name' => 'STATUS_ARCHIVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ARCHIVED\'',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 88,
            'startFilePos' => 900,
            'endTokenPos' => 88,
            'endFilePos' => 909,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 97,
            'startFilePos' => 941,
            'endTokenPos' => 97,
            'endFilePos' => 955,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
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
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'vendor_id\', \'sku\', \'name\', \'description\', \'status\', \'currency\', \'price_minor\', \'attributes\']',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 42,
            'startTokenPos' => 106,
            'startFilePos' => 985,
            'endTokenPos' => 135,
            'endFilePos' => 1114,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 42,
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
        'startLine' => 44,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Modules\\Catalog\\Models',
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'currentClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'aliasName' => NULL,
      ),
      'newFactory' => 
      array (
        'name' => 'newFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return \\Database\\Factories\\CatalogItemFactory
 */',
        'startLine' => 55,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Modules\\Catalog\\Models',
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'currentClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
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
        'startLine' => 60,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Catalog\\Models',
        'declaringClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'implementingClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        'currentClassName' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
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