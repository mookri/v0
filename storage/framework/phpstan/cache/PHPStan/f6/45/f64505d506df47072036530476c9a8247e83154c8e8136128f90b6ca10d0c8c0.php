<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Onboarding\Models\ApplicationDocument.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Onboarding\Models\ApplicationDocument
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-f7b243e881fef8f113469c3495f5598d4a308289c2e906853ab393f00813b6a4',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Onboarding/Models/ApplicationDocument.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Onboarding\\Models',
    'name' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
    'shortName' => 'ApplicationDocument',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Application document metadata (bytes in object storage; this row is the
 * tenant-scoped record). Verification transitions PENDING→VERIFIED/
 * REJECTED with machine-readable reason codes.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $application_id
 * @property string $kind
 * @property string $status
 * @property string $object_key
 * @property string|null $sha256
 * @property string|null $reason_code
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 51,
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
      'STATUS_PENDING' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'name' => 'STATUS_PENDING',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'PENDING\'',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 28,
            'startTokenPos' => 54,
            'startFilePos' => 763,
            'endTokenPos' => 54,
            'endFilePos' => 771,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 28,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'STATUS_VERIFIED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'name' => 'STATUS_VERIFIED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'VERIFIED\'',
          'attributes' => 
          array (
            'startLine' => 30,
            'endLine' => 30,
            'startTokenPos' => 65,
            'startFilePos' => 810,
            'endTokenPos' => 65,
            'endFilePos' => 819,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'STATUS_REJECTED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'name' => 'STATUS_REJECTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'REJECTED\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 76,
            'startFilePos' => 858,
            'endTokenPos' => 76,
            'endFilePos' => 867,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 85,
            'startFilePos' => 899,
            'endTokenPos' => 85,
            'endFilePos' => 913,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'application_id\', \'kind\', \'status\', \'object_key\', \'sha256\', \'reason_code\']',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 39,
            'startTokenPos' => 94,
            'startFilePos' => 943,
            'endTokenPos' => 117,
            'endFilePos' => 1053,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 39,
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
        'startLine' => 41,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
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