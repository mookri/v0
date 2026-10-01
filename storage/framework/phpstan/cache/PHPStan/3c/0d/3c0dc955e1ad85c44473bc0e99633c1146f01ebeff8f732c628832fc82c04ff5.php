<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Modules\Onboarding\OnboardingTrackSeeder.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Modules\Onboarding\OnboardingTrackSeeder
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-d2fd603481111787d4f269a14049ff8cc4a45c763813191a4fb392b7b0b5af1c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Modules/Onboarding/OnboardingTrackSeeder.php',
      ),
    ),
    'namespace' => 'App\\Modules\\Onboarding',
    'name' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
    'shortName' => 'OnboardingTrackSeeder',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Supply-track seeding on invite acceptance: pandit/vendor/individual
 * tracks materialize a DRAFT application pinned to the current track
 * version. Customer/staff tracks seed nothing here (customer records land
 * with the customer module; staff needs no profile row).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 40,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'App\\Platform\\Identity\\Contracts\\TrackSeeder',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'VERSIONS' => 
      array (
        'declaringClassName' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        'implementingClassName' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        'name' => 'VERSIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'pandit\' => \'pandit-v1\', \'partner-vendor\' => \'partner-vendor-v1\', \'partner-individual\' => \'partner-individual-v1\']',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 20,
            'startTokenPos' => 39,
            'startFilePos' => 516,
            'endTokenPos' => 62,
            'endFilePos' => 661,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'seed' => 
      array (
        'name' => 'seed',
        'parameters' => 
        array (
          'track' => 
          array (
            'name' => 'track',
            'default' => NULL,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 26,
            'endColumn' => 38,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'identityId' => 
          array (
            'name' => 'identityId',
            'default' => NULL,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 41,
            'endColumn' => 58,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
                  'name' => 'array',
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
        'startLine' => 22,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Modules\\Onboarding',
        'declaringClassName' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        'implementingClassName' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        'currentClassName' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
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