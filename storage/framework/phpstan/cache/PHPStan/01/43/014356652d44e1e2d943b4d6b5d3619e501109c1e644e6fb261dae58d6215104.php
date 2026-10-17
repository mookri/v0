<?php declare(strict_types = 1);

// ftm-C:\Users\Victo\Downloads\webproj\v0-navdhya-ritual-services\platform-navdhya\app\Providers\AppServiceProvider.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.3',
   'data' => 
  array (
    0 => 
    array (
      '1f3c796da0aba754565b9f851c8ef04f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'checkcapability' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckCapability',
          'checkscope' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckScope',
          'requiresacr' => 'App\\Platform\\Authz\\Http\\Middleware\\RequiresAcr',
          'throttletier' => 'App\\Platform\\Authz\\Http\\Middleware\\ThrottleTier',
          'authenticateany' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateAny',
          'authenticatehmactoken' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
          'dbotpchallengestore' => 'App\\Platform\\Identity\\Otp\\DbOtpChallengeStore',
          'msg91client' => 'App\\Platform\\Identity\\Otp\\Msg91Client',
          'msg91widgetprovider' => 'App\\Platform\\Identity\\Otp\\Msg91WidgetProvider',
          'otpchallengemanager' => 'App\\Platform\\Identity\\Otp\\OtpChallengeManager',
          'otpchallengestore' => 'App\\Platform\\Identity\\Otp\\OtpChallengeStore',
          'otpproviderconfigurationexception' => 'App\\Platform\\Identity\\Otp\\OtpProviderConfigurationException',
          'otpproviderregistry' => 'App\\Platform\\Identity\\Otp\\OtpProviderRegistry',
          'verifiesotptokens' => 'App\\Platform\\Identity\\Otp\\VerifiesOtpTokens',
          'checkoauthgrant' => 'App\\Platform\\Identity\\OAuth\\CheckOAuthGrant',
          'createclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\CreateClientCommand',
          'listdevicecodescommand' => 'App\\Platform\\Identity\\OAuth\\Console\\ListDeviceCodesCommand',
          'revokeclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeClientCommand',
          'revokedevicecodecommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeDeviceCodeCommand',
          'rotateclientsecretcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RotateClientSecretCommand',
          'custodyaccesstoken' => 'App\\Platform\\Identity\\OAuth\\CustodyAccessToken',
          'rotatingrefreshtokenrepository' => 'App\\Platform\\Identity\\OAuth\\RotatingRefreshTokenRepository',
          'kmshttpsigner' => 'App\\Platform\\Identity\\OAuth\\Signing\\KmsHttpSigner',
          'signingcustody' => 'App\\Platform\\Identity\\OAuth\\Signing\\SigningCustody',
          'signstokenremotely' => 'App\\Platform\\Identity\\OAuth\\Signing\\SignsTokenRemotely',
          'resolvetenantcontext' => 'App\\Platform\\Tenancy\\Http\\Middleware\\ResolveTenantContext',
          'route' => 'Illuminate\\Support\\Facades\\Route',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          'refreshtokenrepository' => 'Laravel\\Passport\\Bridge\\RefreshTokenRepository',
          'passport' => 'Laravel\\Passport\\Passport',
        ),
         'className' => 'App\\Providers\\AppServiceProvider',
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
      'd49417fef9bc90f9c7b297bb76ca249f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'checkcapability' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckCapability',
          'checkscope' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckScope',
          'requiresacr' => 'App\\Platform\\Authz\\Http\\Middleware\\RequiresAcr',
          'throttletier' => 'App\\Platform\\Authz\\Http\\Middleware\\ThrottleTier',
          'authenticateany' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateAny',
          'authenticatehmactoken' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
          'dbotpchallengestore' => 'App\\Platform\\Identity\\Otp\\DbOtpChallengeStore',
          'msg91client' => 'App\\Platform\\Identity\\Otp\\Msg91Client',
          'msg91widgetprovider' => 'App\\Platform\\Identity\\Otp\\Msg91WidgetProvider',
          'otpchallengemanager' => 'App\\Platform\\Identity\\Otp\\OtpChallengeManager',
          'otpchallengestore' => 'App\\Platform\\Identity\\Otp\\OtpChallengeStore',
          'otpproviderconfigurationexception' => 'App\\Platform\\Identity\\Otp\\OtpProviderConfigurationException',
          'otpproviderregistry' => 'App\\Platform\\Identity\\Otp\\OtpProviderRegistry',
          'verifiesotptokens' => 'App\\Platform\\Identity\\Otp\\VerifiesOtpTokens',
          'checkoauthgrant' => 'App\\Platform\\Identity\\OAuth\\CheckOAuthGrant',
          'createclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\CreateClientCommand',
          'listdevicecodescommand' => 'App\\Platform\\Identity\\OAuth\\Console\\ListDeviceCodesCommand',
          'revokeclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeClientCommand',
          'revokedevicecodecommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeDeviceCodeCommand',
          'rotateclientsecretcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RotateClientSecretCommand',
          'custodyaccesstoken' => 'App\\Platform\\Identity\\OAuth\\CustodyAccessToken',
          'rotatingrefreshtokenrepository' => 'App\\Platform\\Identity\\OAuth\\RotatingRefreshTokenRepository',
          'kmshttpsigner' => 'App\\Platform\\Identity\\OAuth\\Signing\\KmsHttpSigner',
          'signingcustody' => 'App\\Platform\\Identity\\OAuth\\Signing\\SigningCustody',
          'signstokenremotely' => 'App\\Platform\\Identity\\OAuth\\Signing\\SignsTokenRemotely',
          'resolvetenantcontext' => 'App\\Platform\\Tenancy\\Http\\Middleware\\ResolveTenantContext',
          'route' => 'Illuminate\\Support\\Facades\\Route',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          'refreshtokenrepository' => 'Laravel\\Passport\\Bridge\\RefreshTokenRepository',
          'passport' => 'Laravel\\Passport\\Passport',
        ),
         'className' => 'App\\Providers\\AppServiceProvider',
         'functionName' => 'register',
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
      'f19db2306c7491e97262a83fb1a50a30' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'App\\Providers',
         'uses' => 
        array (
          'checkcapability' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckCapability',
          'checkscope' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckScope',
          'requiresacr' => 'App\\Platform\\Authz\\Http\\Middleware\\RequiresAcr',
          'throttletier' => 'App\\Platform\\Authz\\Http\\Middleware\\ThrottleTier',
          'authenticateany' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateAny',
          'authenticatehmactoken' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
          'dbotpchallengestore' => 'App\\Platform\\Identity\\Otp\\DbOtpChallengeStore',
          'msg91client' => 'App\\Platform\\Identity\\Otp\\Msg91Client',
          'msg91widgetprovider' => 'App\\Platform\\Identity\\Otp\\Msg91WidgetProvider',
          'otpchallengemanager' => 'App\\Platform\\Identity\\Otp\\OtpChallengeManager',
          'otpchallengestore' => 'App\\Platform\\Identity\\Otp\\OtpChallengeStore',
          'otpproviderconfigurationexception' => 'App\\Platform\\Identity\\Otp\\OtpProviderConfigurationException',
          'otpproviderregistry' => 'App\\Platform\\Identity\\Otp\\OtpProviderRegistry',
          'verifiesotptokens' => 'App\\Platform\\Identity\\Otp\\VerifiesOtpTokens',
          'checkoauthgrant' => 'App\\Platform\\Identity\\OAuth\\CheckOAuthGrant',
          'createclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\CreateClientCommand',
          'listdevicecodescommand' => 'App\\Platform\\Identity\\OAuth\\Console\\ListDeviceCodesCommand',
          'revokeclientcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeClientCommand',
          'revokedevicecodecommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RevokeDeviceCodeCommand',
          'rotateclientsecretcommand' => 'App\\Platform\\Identity\\OAuth\\Console\\RotateClientSecretCommand',
          'custodyaccesstoken' => 'App\\Platform\\Identity\\OAuth\\CustodyAccessToken',
          'rotatingrefreshtokenrepository' => 'App\\Platform\\Identity\\OAuth\\RotatingRefreshTokenRepository',
          'kmshttpsigner' => 'App\\Platform\\Identity\\OAuth\\Signing\\KmsHttpSigner',
          'signingcustody' => 'App\\Platform\\Identity\\OAuth\\Signing\\SigningCustody',
          'signstokenremotely' => 'App\\Platform\\Identity\\OAuth\\Signing\\SignsTokenRemotely',
          'resolvetenantcontext' => 'App\\Platform\\Tenancy\\Http\\Middleware\\ResolveTenantContext',
          'route' => 'Illuminate\\Support\\Facades\\Route',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
          'refreshtokenrepository' => 'Laravel\\Passport\\Bridge\\RefreshTokenRepository',
          'passport' => 'Laravel\\Passport\\Passport',
        ),
         'className' => 'App\\Providers\\AppServiceProvider',
         'functionName' => 'boot',
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
    ),
    1 => 
    array (
      'C:\\Users\\Victo\\Downloads\\webproj\\v0-navdhya-ritual-services\\platform-navdhya\\app\\Providers\\AppServiceProvider.php' => 'e3ef44b37edeb0065c524e4d679a7fe1e3a31008fade28dee9917c5782ae8cad',
    ),
  ),
));