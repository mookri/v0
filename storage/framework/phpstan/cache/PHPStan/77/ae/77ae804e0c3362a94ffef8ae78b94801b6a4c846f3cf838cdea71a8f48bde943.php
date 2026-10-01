<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Onboarding\Models\OtpChallenge.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Onboarding\Models\OtpChallenge
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-e01c265c6e2259467d2e31946b311b39bfe3fdc672d92c84996839391aa4eda5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Onboarding/Models/OtpChallenge.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Onboarding\\Models',
    'name' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
    'shortName' => 'OtpChallenge',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * OTP challenge row: hashed code, expiry, attempt accounting. Codes never
 * persist in plaintext; verification consumes the row.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $identifier
 * @property string $code_hash
 * @property \\Illuminate\\Support\\Carbon $expires_at
 * @property int $attempts
 * @property int $max_attempts
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 21,
    'endLine' => 48,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
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
            'startFilePos' => 667,
            'endTokenPos' => 52,
            'endFilePos' => 681,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'identifier\', \'code_hash\', \'expires_at\', \'attempts\', \'max_attempts\']',
          'attributes' => 
          array (
            'startLine' => 28,
            'endLine' => 31,
            'startTokenPos' => 61,
            'startFilePos' => 711,
            'endTokenPos' => 81,
            'endFilePos' => 815,
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
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
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
        'startLine' => 38,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Onboarding\\Models',
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
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