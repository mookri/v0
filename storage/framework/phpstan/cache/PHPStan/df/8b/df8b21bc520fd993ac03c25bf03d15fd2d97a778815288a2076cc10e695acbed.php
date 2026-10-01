<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Onboarding\Models\PartnerApplication.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Onboarding\Models\PartnerApplication
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-c8be0291c4691fbe9279f42195baf207c7d739b4a4fbb5c9548215d0bbbf1eba',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Onboarding/Models/PartnerApplication.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Onboarding\\Models',
    'name' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
    'shortName' => 'PartnerApplication',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Partner application row (onboarding stage 1 port): tenant-scoped,
 * version-pinned track, lifecycle states DRAFT→SUBMITTED→UNDER_REVIEW→
 * APPROVED/REJECTED (+APPEALED/WITHDRAWN). Connection follows the ambient
 * tenant (TEN-012) via getConnectionName.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $identity_id
 * @property string $track
 * @property string $track_version
 * @property string $status
 * @property string $market
 * @property string|null $supersedes
 * @property \\Illuminate\\Support\\Carbon|null $submitted_at
 * @property \\Illuminate\\Support\\Carbon|null $reviewed_at
 * @property string|null $reviewer_id
 * @property string|null $rejection_code
 * @property array<string, mixed>|null $evidence
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 86,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_DRAFT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'DRAFT\'',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 66,
            'startFilePos' => 1220,
            'endTokenPos' => 66,
            'endFilePos' => 1226,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
      'STATUS_SUBMITTED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_SUBMITTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'SUBMITTED\'',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 77,
            'startFilePos' => 1266,
            'endTokenPos' => 77,
            'endFilePos' => 1276,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'STATUS_UNDER_REVIEW' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_UNDER_REVIEW',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'UNDER_REVIEW\'',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 88,
            'startFilePos' => 1319,
            'endTokenPos' => 88,
            'endFilePos' => 1332,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 54,
      ),
      'STATUS_APPROVED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_APPROVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'APPROVED\'',
          'attributes' => 
          array (
            'startLine' => 43,
            'endLine' => 43,
            'startTokenPos' => 99,
            'startFilePos' => 1371,
            'endTokenPos' => 99,
            'endFilePos' => 1380,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 43,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'STATUS_REJECTED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_REJECTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'REJECTED\'',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 110,
            'startFilePos' => 1419,
            'endTokenPos' => 110,
            'endFilePos' => 1428,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'STATUS_APPEALED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_APPEALED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'APPEALED\'',
          'attributes' => 
          array (
            'startLine' => 47,
            'endLine' => 47,
            'startTokenPos' => 121,
            'startFilePos' => 1467,
            'endTokenPos' => 121,
            'endFilePos' => 1476,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 47,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 46,
      ),
      'STATUS_WITHDRAWN' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'STATUS_WITHDRAWN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'WITHDRAWN\'',
          'attributes' => 
          array (
            'startLine' => 49,
            'endLine' => 49,
            'startTokenPos' => 132,
            'startFilePos' => 1516,
            'endTokenPos' => 132,
            'endFilePos' => 1526,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 49,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 51,
            'endLine' => 51,
            'startTokenPos' => 141,
            'startFilePos' => 1558,
            'endTokenPos' => 141,
            'endFilePos' => 1572,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'identity_id\', \'track\', \'track_version\', \'status\', \'market\', \'supersedes\', \'submitted_at\', \'reviewed_at\', \'reviewer_id\', \'rejection_code\', \'evidence\']',
          'attributes' => 
          array (
            'startLine' => 53,
            'endLine' => 57,
            'startTokenPos' => 150,
            'startFilePos' => 1602,
            'endTokenPos' => 188,
            'endFilePos' => 1796,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 53,
        'endLine' => 57,
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
        'startLine' => 59,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
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
        'startLine' => 68,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
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
 * @return \\Database\\Factories\\PartnerApplicationFactory
 */',
        'startLine' => 82,
        'endLine' => 85,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
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