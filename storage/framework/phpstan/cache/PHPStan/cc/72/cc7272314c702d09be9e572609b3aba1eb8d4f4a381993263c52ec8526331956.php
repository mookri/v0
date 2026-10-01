<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Catalog\Models\CatalogItem.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Catalog\Models\CatalogItem
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-aa3a076e812a52e8e31298b4c99ca43ad8a2d04375fb70f7e275d3eefa8023b2',
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
    'startLine' => 23,
    'endLine' => 69,
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
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 66,
            'startFilePos' => 775,
            'endTokenPos' => 66,
            'endFilePos' => 781,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
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
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 77,
            'startFilePos' => 818,
            'endTokenPos' => 77,
            'endFilePos' => 825,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
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
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 88,
            'startFilePos' => 864,
            'endTokenPos' => 88,
            'endFilePos' => 873,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
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
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 97,
            'startFilePos' => 905,
            'endTokenPos' => 97,
            'endFilePos' => 919,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
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
          'code' => '[\'tenant_id\', \'sku\', \'name\', \'description\', \'status\', \'currency\', \'price_minor\', \'attributes\']',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 41,
            'startTokenPos' => 106,
            'startFilePos' => 949,
            'endTokenPos' => 132,
            'endFilePos' => 1065,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 41,
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
        'startLine' => 43,
        'endLine' => 49,
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
        'startLine' => 54,
        'endLine' => 57,
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
        'startLine' => 59,
        'endLine' => 68,
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