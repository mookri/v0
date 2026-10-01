<?php declare(strict_types = 1);

// odsl-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Platform\Audit\AuditLog.php-PHPStan\BetterReflection\Reflection\ReflectionClass-App\Platform\Audit\AuditLog
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.70.0.3-8.5.6-44dbcff14425d3c929904b784ac92a42563058c9f680e38e1eb9f0476fdc7c0f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'App\\Platform\\Audit\\AuditLog',
        'filename' => 'C:/Users/Victo/Downloads/webproj/v0-navdhya-ritual-services/platform-navdhya/app/Platform/Audit/AuditLog.php',
      ),
    ),
    'namespace' => 'App\\Platform\\Audit',
    'name' => 'App\\Platform\\Audit\\AuditLog',
    'shortName' => 'AuditLog',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Audit log row (K-025) on the tenant data connection (TEN-003 §2.2):
 * audit rows MUST commit in the same database transaction as the domain
 * change they record, so they follow the tenant\'s db_mode connection.
 * Rows form a hash chain per tenant (prev_hash/hash) so tampering is
 * detectable. The table carries occurred_at instead of Laravel
 * timestamps; tenant_id is mass-assignable because the kernel audit
 * writer always stamps it explicitly from the resolved context.
 *
 * @property string $id
 * @property string $tenant_id
 * @property array<string, mixed>|null $actor
 * @property string $action
 * @property array<string, mixed>|null $resource
 * @property array<string, mixed>|null $before
 * @property array<string, mixed>|null $after
 * @property string|null $request_id
 * @property string|null $prev_hash
 * @property string $hash
 * @property \\Illuminate\\Support\\Carbon $occurred_at
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 30,
    'endLine' => 73,
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
      'UPDATED_AT' => 
      array (
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'name' => 'UPDATED_AT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 35,
            'endLine' => 35,
            'startTokenPos' => 54,
            'startFilePos' => 1202,
            'endTokenPos' => 54,
            'endFilePos' => 1205,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 35,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'timestamps' => 
      array (
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 37,
            'endLine' => 37,
            'startTokenPos' => 63,
            'startFilePos' => 1234,
            'endTokenPos' => 63,
            'endFilePos' => 1238,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'connection' => 
      array (
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'name' => 'connection',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'tenant_shared\'',
          'attributes' => 
          array (
            'startLine' => 39,
            'endLine' => 39,
            'startTokenPos' => 72,
            'startFilePos' => 1270,
            'endTokenPos' => 72,
            'endFilePos' => 1284,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 39,
        'endLine' => 39,
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
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'tenant_id\', \'actor\', \'action\', \'resource\', \'before\', \'after\', \'request_id\', \'prev_hash\', \'hash\', \'occurred_at\']',
          'attributes' => 
          array (
            'startLine' => 58,
            'endLine' => 61,
            'startTokenPos' => 149,
            'startFilePos' => 1895,
            'endTokenPos' => 181,
            'endFilePos' => 2030,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 61,
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
        'docComment' => '/**
 * Tenant data models resolve their connection from the ambient context
 * (TEN-012): the resolver routes dedicated tenants to their own
 * connection. Unresolved contexts fall back to the shared connection;
 * writes still fail closed via BelongsToTenant.
 */',
        'startLine' => 47,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'App\\Platform\\Audit',
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'currentClassName' => 'App\\Platform\\Audit\\AuditLog',
        'aliasName' => NULL,
      ),
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
        'startLine' => 63,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'App\\Platform\\Audit',
        'declaringClassName' => 'App\\Platform\\Audit\\AuditLog',
        'implementingClassName' => 'App\\Platform\\Audit\\AuditLog',
        'currentClassName' => 'App\\Platform\\Audit\\AuditLog',
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