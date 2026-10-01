<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Identity\Models\Invite.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Identity\Models\Invite
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-0de2bf389d549f256488f549a868abe169458ce7d774131029d76438d4f24719',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Identity\\Models\\Invite',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Identity/Models/Invite.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Identity\\Models',
    'name' => 'App\\Platform\\Identity\\Models\\Invite',
    'shortName' => 'Invite',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Track-scoped invite (central): token stored as SHA-256 hash, single-use
 * lifecycle ISSUED → ACCEPTED/EXPIRED/REVOKED, per-track seeding on accept.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $track
 * @property array<string>|null $roles
 * @property string $identifier
 * @property string $token_hash
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon $expires_at
 * @property string|null $accepted_by
 * @property string|null $created_by
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 26,
    'endLine' => 62,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
    ),
    'immediateConstants' => 
    array (
      'STATE_ISSUED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'STATE_ISSUED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ISSUED\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 66,
            'startFilePos' => 938,
            'endTokenPos' => 66,
            'endFilePos' => 945,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
      'STATE_ACCEPTED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'STATE_ACCEPTED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'ACCEPTED\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 77,
            'startFilePos' => 983,
            'endTokenPos' => 77,
            'endFilePos' => 992,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'STATE_EXPIRED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'STATE_EXPIRED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'EXPIRED\'',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 88,
            'startFilePos' => 1029,
            'endTokenPos' => 88,
            'endFilePos' => 1037,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'STATE_REVOKED' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'STATE_REVOKED',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'REVOKED\'',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 99,
            'startFilePos' => 1074,
            'endTokenPos' => 99,
            'endFilePos' => 1082,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'connection' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'central\'',
          'attributes' => 
          array (
            'startLine' => 40,
            'endLine' => 40,
            'startTokenPos' => 108,
            'startFilePos' => 1114,
            'endTokenPos' => 108,
            'endFilePos' => 1122,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 40,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'track\', \'roles\', \'identifier\', \'token_hash\', \'state\', \'expires_at\', \'accepted_by\', \'created_by\']',
          'attributes' => 
          array (
            'startLine' => 42,
            'endLine' => 45,
            'startTokenPos' => 117,
            'startFilePos' => 1152,
            'endTokenPos' => 146,
            'endFilePos' => 1285,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 42,
        'endLine' => 45,
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
        'startLine' => 47,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Identity\\Models',
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'currentClassName' => 'App\\Platform\\Identity\\Models\\Invite',
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
 * @return Factory<Invite>
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'App\\Platform\\Identity\\Models',
        'declaringClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'implementingClassName' => 'App\\Platform\\Identity\\Models\\Invite',
        'currentClassName' => 'App\\Platform\\Identity\\Models\\Invite',
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