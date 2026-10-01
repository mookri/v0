<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Onboarding\Models\ReviewRecord.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Onboarding\Models\ReviewRecord
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-1e31acde4f7acf7d733187ad1c49ffb06823695cd10344b688b0293ef4c58c21',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Onboarding/Models/ReviewRecord.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Onboarding\\Models',
    'name' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
    'shortName' => 'ReviewRecord',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Review decision record (append-only audit evidence).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $application_id
 * @property string $reviewer_id
 * @property string $decision
 * @property string|null $reason_code
 * @property string|null $note
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
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
      'DECISION_APPROVED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'name' => 'DECISION_APPROVED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'APPROVED\'',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 54,
            'startFilePos' => 596,
            'endTokenPos' => 54,
            'endFilePos' => 605,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'DECISION_REJECTED' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'name' => 'DECISION_REJECTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'REJECTED\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 65,
            'startFilePos' => 646,
            'endTokenPos' => 65,
            'endFilePos' => 655,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'DECISION_REQUEST_INFO' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'name' => 'DECISION_REQUEST_INFO',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'REQUEST_INFO\'',
          'attributes' => 
          array (
            'startLine' => 29,
            'endLine' => 29,
            'startTokenPos' => 76,
            'startFilePos' => 700,
            'endTokenPos' => 76,
            'endFilePos' => 713,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 56,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 85,
            'startFilePos' => 745,
            'endTokenPos' => 85,
            'endFilePos' => 759,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'application_id\', \'reviewer_id\', \'decision\', \'reason_code\', \'note\']',
          'attributes' => 
          array (
            'startLine' => 33,
            'endLine' => 36,
            'startTokenPos' => 94,
            'startFilePos' => 789,
            'endTokenPos' => 114,
            'endFilePos' => 892,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 36,
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
        'declaringClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'implementingClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        'currentClassName' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
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