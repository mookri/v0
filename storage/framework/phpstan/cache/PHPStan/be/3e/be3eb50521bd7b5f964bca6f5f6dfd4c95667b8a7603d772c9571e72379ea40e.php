<?php declare(strict_types = 1);

// ftm-C:\Users\Victo\Downloads\webproj\navdhya-complete-backend\app\Providers\PlatformServiceProvider.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.3',
   'data' => 
  array (
    0 => 
    array (
      '24f58f67098cc783ac15df565b89a27f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
          'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
          'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
          'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
          'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
          'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'className' => 'App\\Providers\\PlatformServiceProvider',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '9314cdaa14fb5f845e56a4cab628b21d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
          'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
          'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
          'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
          'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
          'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'className' => 'App\\Providers\\PlatformServiceProvider',
         'functionName' => 'register',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Providers',
           'uses' => 
          array (
            'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
            'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
            'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
            'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
            'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
            'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
            'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
            'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
            'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
            'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
            'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            'role' => 'App\\Platform\\Authz\\Models\\Role',
            'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
            'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
            'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
            'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
            'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
            'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
            'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
            'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
            'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            'model' => 'Illuminate\\Database\\Eloquent\\Model',
            'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          ),
           'className' => 'App\\Providers\\PlatformServiceProvider',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '1e1a728590e8326c00832ab58f57acae' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
          'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
          'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
          'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
          'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
          'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'className' => 'App\\Providers\\PlatformServiceProvider',
         'functionName' => 'boot',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Providers',
           'uses' => 
          array (
            'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
            'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
            'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
            'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
            'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
            'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
            'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
            'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
            'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
            'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
            'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            'role' => 'App\\Platform\\Authz\\Models\\Role',
            'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
            'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
            'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
            'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
            'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
            'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
            'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
            'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
            'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            'model' => 'Illuminate\\Database\\Eloquent\\Model',
            'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          ),
           'className' => 'App\\Providers\\PlatformServiceProvider',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '4a51b5e2282266c1bd03fa95541b4dd0' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
          'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
          'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
          'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
          'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
          'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'className' => 'App\\Providers\\PlatformServiceProvider',
         'functionName' => 'registerAuthzCacheInvalidation',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'App\\Providers',
           'uses' => 
          array (
            'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
            'authzcacheinvalidator' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
            'capabilityauthorizer' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
            'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
            'conditionevaluator' => 'App\\Platform\\Authz\\ConditionEvaluator',
            'authzexplaincommand' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
            'syncpermissionscommand' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
            'decisioncache' => 'App\\Platform\\Authz\\DecisionCache',
            'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
            'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
            'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            'role' => 'App\\Platform\\Authz\\Models\\Role',
            'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
            'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
            'policyvalidator' => 'App\\Platform\\Authz\\PolicyValidator',
            'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
            'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
            'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
            'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
            'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
            'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            'model' => 'Illuminate\\Database\\Eloquent\\Model',
            'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          ),
           'className' => 'App\\Providers\\PlatformServiceProvider',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      'C:\\Users\\Victo\\Downloads\\webproj\\navdhya-complete-backend\\app\\Providers\\PlatformServiceProvider.php' => 'ee286989fef8932aadc1150e41b73c8da377c8ca540d0686342b9322030584e3',
    ),
  ),
));