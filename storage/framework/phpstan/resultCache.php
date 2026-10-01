<?php declare(strict_types = 1);

return [
	'lastFullAnalysisTime' => 1790837364,
	'meta' => array (
  'cacheVersion' => 'v14-relativePaths',
  'phpstanVersion' => '2.2.9',
  'fnsr' => false,
  'metaExtensions' => 
  array (
  ),
  'phpVersion' => 80506,
  'projectConfig' => '{conditionalTags: {Larastan\\Larastan\\Rules\\NoEnvCallsOutsideOfConfigRule: {phpstan.rules.rule: %noEnvCallsOutsideOfConfig%}, Larastan\\Larastan\\Rules\\NoModelMakeRule: {phpstan.rules.rule: %noModelMake%}, Larastan\\Larastan\\Rules\\NoUnnecessaryCollectionCallRule: {phpstan.rules.rule: %noUnnecessaryCollectionCall%}, Larastan\\Larastan\\Rules\\NoUnnecessaryEnumerableToArrayCallsRule: {phpstan.rules.rule: %noUnnecessaryEnumerableToArrayCalls%}, Larastan\\Larastan\\Rules\\OctaneCompatibilityRule: {phpstan.rules.rule: %checkOctaneCompatibility%}, Larastan\\Larastan\\Rules\\UnusedViewsRule: {phpstan.rules.rule: %checkUnusedViews%}, Larastan\\Larastan\\Rules\\NoMissingTranslationsRule: {phpstan.rules.rule: %checkMissingTranslations%}, Larastan\\Larastan\\Rules\\ModelAppendsRule: {phpstan.rules.rule: %checkModelAppends%}, Larastan\\Larastan\\Rules\\NoPublicModelScopeAndAccessorRule: {phpstan.rules.rule: %checkModelMethodVisibility%}, Larastan\\Larastan\\Rules\\NoAuthFacadeInRequestScopeRule: {phpstan.rules.rule: %checkAuthCallsWhenInRequestScope%}, Larastan\\Larastan\\Rules\\NoAuthHelperInRequestScopeRule: {phpstan.rules.rule: %checkAuthCallsWhenInRequestScope%}, Larastan\\Larastan\\ReturnTypes\\Helpers\\EnvFunctionDynamicFunctionReturnTypeExtension: {phpstan.broker.dynamicFunctionReturnTypeExtension: %generalizeEnvReturnType%}, Larastan\\Larastan\\ReturnTypes\\Helpers\\ConfigFunctionDynamicFunctionReturnTypeExtension: {phpstan.broker.dynamicFunctionReturnTypeExtension: %checkConfigTypes%}, Larastan\\Larastan\\ReturnTypes\\ConfigRepositoryDynamicMethodReturnTypeExtension: {phpstan.broker.dynamicMethodReturnTypeExtension: %checkConfigTypes%}, Larastan\\Larastan\\ReturnTypes\\ConfigFacadeCollectionDynamicStaticMethodReturnTypeExtension: {phpstan.broker.dynamicStaticMethodReturnTypeExtension: %checkConfigTypes%}, Larastan\\Larastan\\Rules\\ConfigCollectionRule: {phpstan.rules.rule: %checkConfigTypes%}}, parameters: {universalObjectCratesClasses: [Illuminate\\Http\\Request, Illuminate\\Support\\Optional], earlyTerminatingFunctionCalls: [abort, dd], mixinExcludeClasses: [Eloquent], bootstrapFiles: [bootstrap.php, phpstan-bootstrap.php], checkOctaneCompatibility: false, noEnvCallsOutsideOfConfig: true, noModelMake: true, noUnnecessaryCollectionCall: true, noUnnecessaryCollectionCallOnly: [], noUnnecessaryCollectionCallExcept: [], noUnnecessaryEnumerableToArrayCalls: false, squashedMigrationsPath: [], databaseMigrationsPath: [], disableMigrationScan: false, disableSchemaScan: false, configDirectories: [], viewDirectories: [], translationDirectories: [], checkModelProperties: false, checkUnusedViews: false, checkMissingTranslations: false, checkModelAppends: true, checkModelMethodVisibility: false, generalizeEnvReturnType: false, checkConfigTypes: false, checkAuthCallsWhenInRequestScope: false, parseModelCastsMethod: false, enableMigrationCache: false, level: 6, paths: [../../../app, ../../../config, ../../../routes], tmpDir: ../../../storage/framework/phpstan, treatPhpDocTypesAsCertain: false}, rules: [Larastan\\Larastan\\Rules\\UselessConstructs\\NoUselessWithFunctionCallsRule, Larastan\\Larastan\\Rules\\UselessConstructs\\NoUselessValueFunctionCallsRule, Larastan\\Larastan\\Rules\\DeferrableServiceProviderMissingProvidesRule, Larastan\\Larastan\\Rules\\ConsoleCommand\\UndefinedArgumentOrOptionRule], services: {{class: Larastan\\Larastan\\Methods\\RelationForwardsCallsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\ModelForwardsCallsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\EloquentBuilderForwardsCallsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\HigherOrderTapProxyExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\HigherOrderCollectionProxyExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\StorageMethodsClassReflectionExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\ContractsMethodsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\FacadesMethodsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\ManagersMethodsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\AuthsMethodsExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\ModelFactoryMethodsClassReflectionExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\RedirectResponseMethodsClassReflectionExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\MacroMethodsClassReflectionExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Methods\\ViewWithMethodsClassReflectionExtension, tags: [phpstan.broker.methodsClassReflectionExtension]}, {class: Larastan\\Larastan\\Properties\\ModelAccessorExtension, tags: [phpstan.broker.propertiesClassReflectionExtension]}, {class: Larastan\\Larastan\\Properties\\ModelPropertyExtension, tags: [phpstan.broker.propertiesClassReflectionExtension]}, {class: Larastan\\Larastan\\Properties\\HigherOrderCollectionProxyPropertyExtension, tags: [phpstan.broker.propertiesClassReflectionExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\HigherOrderTapProxyExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ContainerArrayAccessDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {className: Illuminate\\Contracts\\Container\\Container}}, {class: Larastan\\Larastan\\ReturnTypes\\ContainerArrayAccessDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {className: Illuminate\\Container\\Container}}, {class: Larastan\\Larastan\\ReturnTypes\\ContainerArrayAccessDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {className: Illuminate\\Foundation\\Application}}, {class: Larastan\\Larastan\\ReturnTypes\\ContainerArrayAccessDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {className: Illuminate\\Contracts\\Foundation\\Application}}, {class: Larastan\\Larastan\\Properties\\ModelRelationsExtension, tags: [phpstan.broker.propertiesClassReflectionExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ModelOnlyDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ModelFactoryDynamicStaticMethodReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ModelDynamicStaticMethodReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\AppMakeDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\AuthExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\GuardDynamicStaticMethodReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\AuthManagerExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\DateExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\GuardExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\RequestFileExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\RequestRouteExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\RequestUserExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\EloquentBuilderExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\RelationCollectionExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\TestCaseExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\Support\\CollectionHelper}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\AuthExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\CollectExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\NowAndTodayExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\ResponseExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\ValidatorExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\LiteralExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\CollectionFilterRejectDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\CollectionWhereNotNullDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\NewModelQueryDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\FactoryDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\Types\\AbortIfFunctionTypeSpecifyingExtension, tags: [phpstan.typeSpecifier.functionTypeSpecifyingExtension], arguments: {methodName: abort, negate: false}}, {class: Larastan\\Larastan\\Types\\AbortIfFunctionTypeSpecifyingExtension, tags: [phpstan.typeSpecifier.functionTypeSpecifyingExtension], arguments: {methodName: abort, negate: true}}, {class: Larastan\\Larastan\\Types\\AbortIfFunctionTypeSpecifyingExtension, tags: [phpstan.typeSpecifier.functionTypeSpecifyingExtension], arguments: {methodName: throw, negate: false}}, {class: Larastan\\Larastan\\Types\\AbortIfFunctionTypeSpecifyingExtension, tags: [phpstan.typeSpecifier.functionTypeSpecifyingExtension], arguments: {methodName: throw, negate: true}}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\AppExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\ValueExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\StrExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\TapExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\StorageDynamicStaticMethodReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\Types\\GenericEloquentCollectionTypeNodeResolverExtension, tags: [phpstan.phpDoc.typeNodeResolverExtension]}, {class: Larastan\\Larastan\\Types\\ViewStringTypeNodeResolverExtension, tags: [phpstan.phpDoc.typeNodeResolverExtension]}, {class: Larastan\\Larastan\\Rules\\OctaneCompatibilityRule}, {class: Larastan\\Larastan\\Rules\\NoEnvCallsOutsideOfConfigRule, arguments: {configDirectories: %configDirectories%}}, {class: Larastan\\Larastan\\Rules\\NoModelMakeRule}, {class: Larastan\\Larastan\\Rules\\NoUnnecessaryCollectionCallRule, arguments: {onlyMethods: %noUnnecessaryCollectionCallOnly%, excludeMethods: %noUnnecessaryCollectionCallExcept%}}, {class: Larastan\\Larastan\\Rules\\NoUnnecessaryEnumerableToArrayCallsRule}, {class: Larastan\\Larastan\\Rules\\ModelAppendsRule}, {class: Larastan\\Larastan\\Rules\\NoPublicModelScopeAndAccessorRule}, {class: Larastan\\Larastan\\Types\\GenericEloquentBuilderTypeNodeResolverExtension, tags: [phpstan.phpDoc.typeNodeResolverExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\AppEnvironmentReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {class: Illuminate\\Foundation\\Application}}, {class: Larastan\\Larastan\\ReturnTypes\\AppEnvironmentReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension], arguments: {class: Illuminate\\Contracts\\Foundation\\Application}}, {class: Larastan\\Larastan\\ReturnTypes\\AppFacadeEnvironmentReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\Types\\ModelProperty\\ModelPropertyTypeNodeResolverExtension, tags: [phpstan.phpDoc.typeNodeResolverExtension], arguments: {active: %checkModelProperties%}}, {class: Larastan\\Larastan\\Types\\CollectionOf\\CollectionOfTypeNodeResolverExtension, tags: [phpstan.phpDoc.typeNodeResolverExtension]}, {class: Larastan\\Larastan\\Properties\\MigrationHelper, arguments: {databaseMigrationPath: %databaseMigrationsPath%, disableMigrationScan: %disableMigrationScan%, parser: @migrationsParser, reflectionProvider: @reflectionProvider}}, iamcalSqlParser: {class: Larastan\\Larastan\\SQL\\IamcalSqlParser, autowired: false}, sqlParserFactory: {class: Larastan\\Larastan\\SQL\\SqlParserFactory, arguments: {iamcalSqlParser: @iamcalSqlParser}}, sqlParser: {type: Larastan\\Larastan\\SQL\\SqlParser, factory: [@sqlParserFactory, create]}, {class: Larastan\\Larastan\\Properties\\SquashedMigrationHelper, arguments: {schemaPaths: %squashedMigrationsPath%, disableSchemaScan: %disableSchemaScan%}}, {class: Larastan\\Larastan\\Properties\\ModelCastHelper, arguments: {parser: @currentPhpVersionSimpleDirectParser, parseModelCastsMethod: %parseModelCastsMethod%}}, {class: Larastan\\Larastan\\Properties\\MigrationCache, arguments: {cacheDirectory: %tmpDir%, enabled: %enableMigrationCache%}}, {class: Larastan\\Larastan\\Properties\\ModelPropertyHelper}, {class: Larastan\\Larastan\\Rules\\ModelRuleHelper}, {class: Larastan\\Larastan\\Methods\\BuilderHelper, arguments: {checkProperties: %checkModelProperties%}}, {class: Larastan\\Larastan\\Rules\\RelationExistenceRule, tags: [phpstan.rules.rule]}, {class: Larastan\\Larastan\\Rules\\CheckDispatchArgumentTypesCompatibleWithClassConstructorRule, arguments: {dispatchableClass: Illuminate\\Foundation\\Bus\\Dispatchable}, tags: [phpstan.rules.rule]}, {class: Larastan\\Larastan\\Rules\\CheckDispatchArgumentTypesCompatibleWithClassConstructorRule, arguments: {dispatchableClass: Illuminate\\Foundation\\Events\\Dispatchable}, tags: [phpstan.rules.rule]}, {class: Larastan\\Larastan\\Properties\\Schema\\MySqlDataTypeToPhpTypeConverter}, {class: Larastan\\Larastan\\LarastanStubFilesExtension, tags: [phpstan.stubFilesExtension]}, {class: Larastan\\Larastan\\Rules\\UnusedViewsRule}, {class: Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedEmailViewCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedViewMakeCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedViewFacadeMakeCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedRouteFacadeViewCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedViewInAnotherViewCollector}, {class: Larastan\\Larastan\\Support\\ViewFileHelper, arguments: {viewDirectories: %viewDirectories%}}, {class: Larastan\\Larastan\\Support\\ViewParser, arguments: {parser: @currentPhpVersionSimpleDirectParser}}, {class: Larastan\\Larastan\\Rules\\NoMissingTranslationsRule, arguments: {translationDirectories: %translationDirectories%}}, {class: Larastan\\Larastan\\Collectors\\UsedTranslationFunctionCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedTranslationTranslatorCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedTranslationFacadeCollector, tags: [phpstan.collector]}, {class: Larastan\\Larastan\\Collectors\\UsedTranslationViewCollector}, {class: Larastan\\Larastan\\ReturnTypes\\ApplicationMakeDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ContainerMakeDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ConsoleCommand\\ArgumentDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ConsoleCommand\\HasArgumentDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ConsoleCommand\\OptionDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\ConsoleCommand\\HasOptionDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\TranslatorGetReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\LangGetReturnTypeExtension, tags: [phpstan.broker.dynamicStaticMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\TransHelperReturnTypeExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\DoubleUnderscoreHelperReturnTypeExtension, tags: [phpstan.broker.dynamicFunctionReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\AppMakeHelper}, {class: Larastan\\Larastan\\Internal\\ConsoleApplicationResolver}, {class: Larastan\\Larastan\\Internal\\ConsoleApplicationHelper}, {class: Larastan\\Larastan\\Support\\HigherOrderCollectionProxyHelper}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\ConfigFunctionDynamicFunctionReturnTypeExtension}, {class: Larastan\\Larastan\\ReturnTypes\\ConfigRepositoryDynamicMethodReturnTypeExtension}, {class: Larastan\\Larastan\\ReturnTypes\\ConfigFacadeCollectionDynamicStaticMethodReturnTypeExtension}, {class: Larastan\\Larastan\\Support\\ConfigParser, arguments: {parser: @currentPhpVersionSimpleDirectParser, configPaths: %configDirectories%, treatPhpDocTypesAsCertain: %treatPhpDocTypesAsCertain%}}, {class: Larastan\\Larastan\\Internal\\ConfigHelper}, {class: Larastan\\Larastan\\ReturnTypes\\Helpers\\EnvFunctionDynamicFunctionReturnTypeExtension}, {class: Larastan\\Larastan\\ReturnTypes\\FormRequestSafeDynamicMethodReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\ReturnTypes\\EloquentCollectionMapDynamicReturnTypeExtension, tags: [phpstan.broker.dynamicMethodReturnTypeExtension]}, {class: Larastan\\Larastan\\Rules\\NoAuthFacadeInRequestScopeRule}, {class: Larastan\\Larastan\\Rules\\NoAuthHelperInRequestScopeRule}, {class: Larastan\\Larastan\\Rules\\ConfigCollectionRule}, {class: Illuminate\\Filesystem\\Filesystem, autowired: self}, migrationsParser: {class: PHPStan\\Parser\\CachedParser, arguments: {originalParser: @currentPhpVersionSimpleDirectParser, cachedNodesByStringCountMax: %cache.nodesByStringCountMax%}, autowired: false}}}',
  'analysedPaths' => 
  array (
    0 => '../../../app',
    1 => '../../../config',
    2 => '../../../routes',
  ),
  'scannedFiles' => 
  array (
  ),
  'composerLocks' => 
  array (
    '../../../composer.lock' => 'de29726a8ee2493e27fa5e7cad8e8aa2465b77bf8fd6098062a7607eb690e9e7',
  ),
  'composerInstalled' => 
  array (
    '../../composer/installed.php' => 
    array (
      'versions' => 
      array (
        'brick/math' => 
        array (
          'pretty_version' => '0.14.8',
          'version' => '0.14.8.0',
          'reference' => '63422359a44b7f06cae63c3b429b59e8efcc0629',
          'type' => 'library',
          'install_path' => '../../composer/../brick/math',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'carbonphp/carbon-doctrine-types' => 
        array (
          'pretty_version' => '3.2.0',
          'version' => '3.2.0.0',
          'reference' => '18ba5ddfec8976260ead6e866180bd5d2f71aa1d',
          'type' => 'library',
          'install_path' => '../../composer/../carbonphp/carbon-doctrine-types',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'cordoval/hamcrest-php' => 
        array (
          'dev_requirement' => true,
          'replaced' => 
          array (
            0 => '*',
          ),
        ),
        'davedevelopment/hamcrest-php' => 
        array (
          'dev_requirement' => true,
          'replaced' => 
          array (
            0 => '*',
          ),
        ),
        'dflydev/dot-access-data' => 
        array (
          'pretty_version' => 'v3.0.3',
          'version' => '3.0.3.0',
          'reference' => 'a23a2bf4f31d3518f3ecb38660c95715dfead60f',
          'type' => 'library',
          'install_path' => '../../composer/../dflydev/dot-access-data',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'doctrine/inflector' => 
        array (
          'pretty_version' => '2.1.0',
          'version' => '2.1.0.0',
          'reference' => '6d6c96277ea252fc1304627204c3d5e6e15faa3b',
          'type' => 'library',
          'install_path' => '../../composer/../doctrine/inflector',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'doctrine/lexer' => 
        array (
          'pretty_version' => '3.0.1',
          'version' => '3.0.1.0',
          'reference' => '31ad66abc0fc9e1a1f2d9bc6a42668d2fbbcd6dd',
          'type' => 'library',
          'install_path' => '../../composer/../doctrine/lexer',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'dragonmantank/cron-expression' => 
        array (
          'pretty_version' => 'v3.6.0',
          'version' => '3.6.0.0',
          'reference' => 'd61a8a9604ec1f8c3d150d09db6ce98b32675013',
          'type' => 'library',
          'install_path' => '../../composer/../dragonmantank/cron-expression',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'egulias/email-validator' => 
        array (
          'pretty_version' => '4.0.4',
          'version' => '4.0.4.0',
          'reference' => 'd42c8731f0624ad6bdc8d3e5e9a4524f68801cfa',
          'type' => 'library',
          'install_path' => '../../composer/../egulias/email-validator',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'fakerphp/faker' => 
        array (
          'pretty_version' => 'v1.24.1',
          'version' => '1.24.1.0',
          'reference' => 'e0ee18eb1e6dc3cda3ce9fd97e5a0689a88a64b5',
          'type' => 'library',
          'install_path' => '../../composer/../fakerphp/faker',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'filp/whoops' => 
        array (
          'pretty_version' => '2.18.4',
          'version' => '2.18.4.0',
          'reference' => 'd2102955e48b9fd9ab24280a7ad12ed552752c4d',
          'type' => 'library',
          'install_path' => '../../composer/../filp/whoops',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'fruitcake/php-cors' => 
        array (
          'pretty_version' => 'v1.4.0',
          'version' => '1.4.0.0',
          'reference' => '38aaa6c3fd4c157ffe2a4d10aa8b9b16ba8de379',
          'type' => 'library',
          'install_path' => '../../composer/../fruitcake/php-cors',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'graham-campbell/result-type' => 
        array (
          'pretty_version' => 'v1.2.0',
          'version' => '1.2.0.0',
          'reference' => 'adccca3324eece92ca35463648c12b9e6293c05b',
          'type' => 'library',
          'install_path' => '../../composer/../graham-campbell/result-type',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'guzzlehttp/guzzle' => 
        array (
          'pretty_version' => '7.15.5',
          'version' => '7.15.5.0',
          'reference' => 'ee80339fd9177ba44c49cdb653ff02a4d1106b9a',
          'type' => 'library',
          'install_path' => '../../composer/../guzzlehttp/guzzle',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'guzzlehttp/promises' => 
        array (
          'pretty_version' => '2.5.3',
          'version' => '2.5.3.0',
          'reference' => 'cde49999552d185d64715fe9c1f77a2aadd2f9f1',
          'type' => 'library',
          'install_path' => '../../composer/../guzzlehttp/promises',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'guzzlehttp/psr7' => 
        array (
          'pretty_version' => '2.13.1',
          'version' => '2.13.1.0',
          'reference' => '95e7828100de18b4e269fb1703be530082d5166d',
          'type' => 'library',
          'install_path' => '../../composer/../guzzlehttp/psr7',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'guzzlehttp/uri-template' => 
        array (
          'pretty_version' => 'v1.0.11',
          'version' => '1.0.11.0',
          'reference' => 'd0058dccf4299d70c3d9da3378b8908b32780368',
          'type' => 'library',
          'install_path' => '../../composer/../guzzlehttp/uri-template',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'hamcrest/hamcrest-php' => 
        array (
          'pretty_version' => 'v3.0.0',
          'version' => '3.0.0.0',
          'reference' => 'b61cd040da1a4925bc90a51c074f5297e7c0fa52',
          'type' => 'library',
          'install_path' => '../../composer/../hamcrest/hamcrest-php',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'iamcal/sql-parser' => 
        array (
          'pretty_version' => 'v0.7',
          'version' => '0.7.0.0',
          'reference' => '610392f38de49a44dab08dc1659960a29874c4b8',
          'type' => 'library',
          'install_path' => '../../composer/../iamcal/sql-parser',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'illuminate/auth' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/broadcasting' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/bus' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/cache' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/collections' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/concurrency' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/conditionable' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/config' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/console' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/container' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/contracts' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/cookie' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/database' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/encryption' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/events' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/filesystem' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/hashing' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/http' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/json-schema' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/log' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/macroable' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/mail' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/notifications' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/pagination' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/pipeline' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/process' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/queue' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/redis' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/reflection' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/routing' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/session' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/support' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/testing' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/translation' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/validation' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'illuminate/view' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => 'v12.67.0',
          ),
        ),
        'kodova/hamcrest-php' => 
        array (
          'dev_requirement' => true,
          'replaced' => 
          array (
            0 => '*',
          ),
        ),
        'larastan/larastan' => 
        array (
          'pretty_version' => 'v3.10.0',
          'version' => '3.10.0.0',
          'reference' => '2970f83398154178a739609c244577267c7ee8eb',
          'type' => 'phpstan-extension',
          'install_path' => '../../composer/../larastan/larastan',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'laravel/framework' => 
        array (
          'pretty_version' => 'v12.67.0',
          'version' => '12.67.0.0',
          'reference' => 'fe2cdaba052cbb9f350761ccefc7ac221cbdf0b5',
          'type' => 'library',
          'install_path' => '../../composer/../laravel/framework',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'laravel/pint' => 
        array (
          'pretty_version' => 'v1.30.5',
          'version' => '1.30.5.0',
          'reference' => 'fe4148c503a0e266353d61396b79bbf7f35122df',
          'type' => 'project',
          'install_path' => '../../composer/../laravel/pint',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'laravel/prompts' => 
        array (
          'pretty_version' => 'v0.3.23',
          'version' => '0.3.23.0',
          'reference' => 'b7b4c35e5bc47450f6b6238c6cc9c47ba19b2221',
          'type' => 'library',
          'install_path' => '../../composer/../laravel/prompts',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'laravel/sail' => 
        array (
          'pretty_version' => 'v1.67.0',
          'version' => '1.67.0.0',
          'reference' => '639e03ac12cf23def171770bcab05758045b2642',
          'type' => 'library',
          'install_path' => '../../composer/../laravel/sail',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'laravel/serializable-closure' => 
        array (
          'pretty_version' => 'v2.0.15',
          'version' => '2.0.15.0',
          'reference' => 'dccd8bcb851bb03fcc005df650b708b57cc52661',
          'type' => 'library',
          'install_path' => '../../composer/../laravel/serializable-closure',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'laravel/tinker' => 
        array (
          'pretty_version' => 'v2.11.1',
          'version' => '2.11.1.0',
          'reference' => 'c9f80cc835649b5c1842898fb043f8cc098dd741',
          'type' => 'library',
          'install_path' => '../../composer/../laravel/tinker',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/commonmark' => 
        array (
          'pretty_version' => '2.10.0',
          'version' => '2.10.0.0',
          'reference' => 'd2d1aa8b35e072966c89bc0c66cf926e56767dc4',
          'type' => 'library',
          'install_path' => '../../composer/../league/commonmark',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/config' => 
        array (
          'pretty_version' => 'v1.2.0',
          'version' => '1.2.0.0',
          'reference' => '754b3604fb2984c71f4af4a9cbe7b57f346ec1f3',
          'type' => 'library',
          'install_path' => '../../composer/../league/config',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/flysystem' => 
        array (
          'pretty_version' => '3.35.3',
          'version' => '3.35.3.0',
          'reference' => '5fc8404762179ae514678487b23494fd69b2309c',
          'type' => 'library',
          'install_path' => '../../composer/../league/flysystem',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/flysystem-local' => 
        array (
          'pretty_version' => '3.35.3',
          'version' => '3.35.3.0',
          'reference' => 'a099b24dce160f3b2239043d13d47c4a1a214ea4',
          'type' => 'library',
          'install_path' => '../../composer/../league/flysystem-local',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/mime-type-detection' => 
        array (
          'pretty_version' => '1.17.0',
          'version' => '1.17.0.0',
          'reference' => 'f5f47eff7c48ed1003069a2ca67f316fb4021c76',
          'type' => 'library',
          'install_path' => '../../composer/../league/mime-type-detection',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/uri' => 
        array (
          'pretty_version' => '7.8.1',
          'version' => '7.8.1.0',
          'reference' => '08cf38e3924d4f56238125547b5720496fac8fd4',
          'type' => 'library',
          'install_path' => '../../composer/../league/uri',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'league/uri-interfaces' => 
        array (
          'pretty_version' => '7.8.1',
          'version' => '7.8.1.0',
          'reference' => '85d5c77c5d6d3af6c54db4a78246364908f3c928',
          'type' => 'library',
          'install_path' => '../../composer/../league/uri-interfaces',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'mockery/mockery' => 
        array (
          'pretty_version' => '1.6.15',
          'version' => '1.6.15.0',
          'reference' => '967a801bd188989a5669bd280f252d51c0fdc9ee',
          'type' => 'library',
          'install_path' => '../../composer/../mockery/mockery',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'monolog/monolog' => 
        array (
          'pretty_version' => '3.10.0',
          'version' => '3.10.0.0',
          'reference' => 'b321dd6749f0bf7189444158a3ce785cc16d69b0',
          'type' => 'library',
          'install_path' => '../../composer/../monolog/monolog',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'mtdowling/cron-expression' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => '^1.0',
          ),
        ),
        'myclabs/deep-copy' => 
        array (
          'pretty_version' => '1.14.0',
          'version' => '1.14.0.0',
          'reference' => '8680aa248f8e07bc8fb43f56f0f5fc77a0c96aae',
          'type' => 'library',
          'install_path' => '../../composer/../myclabs/deep-copy',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'nesbot/carbon' => 
        array (
          'pretty_version' => '3.13.2',
          'version' => '3.13.2.0',
          'reference' => 'a1c54919f5fff9800cd03c32bd01defd5a4061cb',
          'type' => 'library',
          'install_path' => '../../composer/../nesbot/carbon',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'nette/schema' => 
        array (
          'pretty_version' => 'v1.3.6',
          'version' => '1.3.6.0',
          'reference' => 'c54350438cd6914616f790a49cb424605f421562',
          'type' => 'library',
          'install_path' => '../../composer/../nette/schema',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'nette/utils' => 
        array (
          'pretty_version' => 'v4.1.5',
          'version' => '4.1.5.0',
          'reference' => 'b043439dbdf954e6c28b5ea7e34b0100f83165e0',
          'type' => 'library',
          'install_path' => '../../composer/../nette/utils',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'nikic/php-parser' => 
        array (
          'pretty_version' => 'v5.8.0',
          'version' => '5.8.0.0',
          'reference' => '044a6a392ff8ad0d61f14370a5fbbd0a0107152f',
          'type' => 'library',
          'install_path' => '../../composer/../nikic/php-parser',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'nunomaduro/collision' => 
        array (
          'pretty_version' => 'v8.9.5',
          'version' => '8.9.5.0',
          'reference' => 'fb53eacd509a1d303858e2d20cfebf2d630254ec',
          'type' => 'library',
          'install_path' => '../../composer/../nunomaduro/collision',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'nunomaduro/termwind' => 
        array (
          'pretty_version' => 'v2.4.0',
          'version' => '2.4.0.0',
          'reference' => '712a31b768f5daea284c2169a7d227031001b9a8',
          'type' => 'library',
          'install_path' => '../../composer/../nunomaduro/termwind',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'phar-io/manifest' => 
        array (
          'pretty_version' => '2.0.4',
          'version' => '2.0.4.0',
          'reference' => '54750ef60c58e43759730615a392c31c80e23176',
          'type' => 'library',
          'install_path' => '../../composer/../phar-io/manifest',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phar-io/version' => 
        array (
          'pretty_version' => '3.2.1',
          'version' => '3.2.1.0',
          'reference' => '4f7fd7836c6f332bb2933569e566a0d6c4cbed74',
          'type' => 'library',
          'install_path' => '../../composer/../phar-io/version',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpoption/phpoption' => 
        array (
          'pretty_version' => '1.10.0',
          'version' => '1.10.0.0',
          'reference' => '67b192b6a42ec03944b972d6e633ddec78ad2c6d',
          'type' => 'library',
          'install_path' => '../../composer/../phpoption/phpoption',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'phpstan/phpstan' => 
        array (
          'pretty_version' => '2.2.9',
          'version' => '2.2.9.0',
          'reference' => '13d6b4f347bad222da436580c8304fa6f83e6bd0',
          'type' => 'library',
          'install_path' => '../../composer/../phpstan/phpstan',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/php-code-coverage' => 
        array (
          'pretty_version' => '11.0.12',
          'version' => '11.0.12.0',
          'reference' => '2c1ed04922802c15e1de5d7447b4856de949cf56',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/php-code-coverage',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/php-file-iterator' => 
        array (
          'pretty_version' => '5.1.1',
          'version' => '5.1.1.0',
          'reference' => '2f3a64888c814fc235386b7387dd5b5ed92ad903',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/php-file-iterator',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/php-invoker' => 
        array (
          'pretty_version' => '5.0.1',
          'version' => '5.0.1.0',
          'reference' => 'c1ca3814734c07492b3d4c5f794f4b0995333da2',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/php-invoker',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/php-text-template' => 
        array (
          'pretty_version' => '4.0.1',
          'version' => '4.0.1.0',
          'reference' => '3e0404dc6b300e6bf56415467ebcb3fe4f33e964',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/php-text-template',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/php-timer' => 
        array (
          'pretty_version' => '7.0.1',
          'version' => '7.0.1.0',
          'reference' => '3b415def83fbcb41f991d9ebf16ae4ad8b7837b3',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/php-timer',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'phpunit/phpunit' => 
        array (
          'pretty_version' => '11.5.56',
          'version' => '11.5.56.0',
          'reference' => '5f83edffa6967c3db468d48a695ec7bcb02e9256',
          'type' => 'library',
          'install_path' => '../../composer/../phpunit/phpunit',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'psr/clock' => 
        array (
          'pretty_version' => '1.0.0',
          'version' => '1.0.0.0',
          'reference' => 'e41a24703d4560fd0acb709162f73b8adfc3aa0d',
          'type' => 'library',
          'install_path' => '../../composer/../psr/clock',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/clock-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0',
          ),
        ),
        'psr/container' => 
        array (
          'pretty_version' => '2.0.2',
          'version' => '2.0.2.0',
          'reference' => 'c71ecc56dfe541dbd90c5360474fbc405f8d5963',
          'type' => 'library',
          'install_path' => '../../composer/../psr/container',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/container-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.1|2.0',
          ),
        ),
        'psr/event-dispatcher' => 
        array (
          'pretty_version' => '1.0.0',
          'version' => '1.0.0.0',
          'reference' => 'dbefd12671e8a14ec7f180cab83036ed26714bb0',
          'type' => 'library',
          'install_path' => '../../composer/../psr/event-dispatcher',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/event-dispatcher-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0',
          ),
        ),
        'psr/http-client' => 
        array (
          'pretty_version' => '1.0.3',
          'version' => '1.0.3.0',
          'reference' => 'bb5906edc1c324c9a05aa0873d40117941e5fa90',
          'type' => 'library',
          'install_path' => '../../composer/../psr/http-client',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/http-client-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0',
          ),
        ),
        'psr/http-factory' => 
        array (
          'pretty_version' => '1.1.0',
          'version' => '1.1.0.0',
          'reference' => '2b4765fddfe3b508ac62f829e852b1501d3f6e8a',
          'type' => 'library',
          'install_path' => '../../composer/../psr/http-factory',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/http-factory-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0',
          ),
        ),
        'psr/http-message' => 
        array (
          'pretty_version' => '2.0',
          'version' => '2.0.0.0',
          'reference' => '402d35bcb92c70c026d1a6a9883f06b2ead23d71',
          'type' => 'library',
          'install_path' => '../../composer/../psr/http-message',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/http-message-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0',
          ),
        ),
        'psr/log' => 
        array (
          'pretty_version' => '3.0.2',
          'version' => '3.0.2.0',
          'reference' => 'f16e1d5863e37f8d8c2a01719f5b34baa2b714d3',
          'type' => 'library',
          'install_path' => '../../composer/../psr/log',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/log-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0|2.0|3.0',
            1 => '3.0.0',
          ),
        ),
        'psr/simple-cache' => 
        array (
          'pretty_version' => '3.0.0',
          'version' => '3.0.0.0',
          'reference' => '764e0b3939f5ca87cb904f570ef9be2d78a07865',
          'type' => 'library',
          'install_path' => '../../composer/../psr/simple-cache',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'psr/simple-cache-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '1.0|2.0|3.0',
          ),
        ),
        'psy/psysh' => 
        array (
          'pretty_version' => 'v0.12.24',
          'version' => '0.12.24.0',
          'reference' => 'ca0fdcf8a7617afa3adfdf1b5fef573dffb69ca1',
          'type' => 'library',
          'install_path' => '../../composer/../psy/psysh',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'qossmic/deptrac' => 
        array (
          'pretty_version' => '2.0.4',
          'version' => '2.0.4.0',
          'reference' => 'a6dbfaf4f20f0ae98b766954f35b8c164c3063ed',
          'type' => 'library',
          'install_path' => '../../composer/../qossmic/deptrac',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'ralouphie/getallheaders' => 
        array (
          'pretty_version' => '3.0.3',
          'version' => '3.0.3.0',
          'reference' => '120b605dfeb996808c31b6477290a714d356e822',
          'type' => 'library',
          'install_path' => '../../composer/../ralouphie/getallheaders',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'ramsey/collection' => 
        array (
          'pretty_version' => '2.1.1',
          'version' => '2.1.1.0',
          'reference' => '344572933ad0181accbf4ba763e85a0306a8c5e2',
          'type' => 'library',
          'install_path' => '../../composer/../ramsey/collection',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'ramsey/uuid' => 
        array (
          'pretty_version' => '4.9.3',
          'version' => '4.9.3.0',
          'reference' => '1df15849d00943a67d677dc9cfd80795f038c9f8',
          'type' => 'library',
          'install_path' => '../../composer/../ramsey/uuid',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'rhumsaa/uuid' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => '4.9.3',
          ),
        ),
        'sebastian/cli-parser' => 
        array (
          'pretty_version' => '3.0.2',
          'version' => '3.0.2.0',
          'reference' => '15c5dd40dc4f38794d383bb95465193f5e0ae180',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/cli-parser',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/code-unit' => 
        array (
          'pretty_version' => '3.0.3',
          'version' => '3.0.3.0',
          'reference' => '54391c61e4af8078e5b276ab082b6d3c54c9ad64',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/code-unit',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/code-unit-reverse-lookup' => 
        array (
          'pretty_version' => '4.0.1',
          'version' => '4.0.1.0',
          'reference' => '183a9b2632194febd219bb9246eee421dad8d45e',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/code-unit-reverse-lookup',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/comparator' => 
        array (
          'pretty_version' => '6.3.3',
          'version' => '6.3.3.0',
          'reference' => '2c95e1e86cb8dd41beb8d502057d1081ccc8eca9',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/comparator',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/complexity' => 
        array (
          'pretty_version' => '4.0.1',
          'version' => '4.0.1.0',
          'reference' => 'ee41d384ab1906c68852636b6de493846e13e5a0',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/complexity',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/diff' => 
        array (
          'pretty_version' => '6.0.2',
          'version' => '6.0.2.0',
          'reference' => 'b4ccd857127db5d41a5b676f24b51371d76d8544',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/diff',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/environment' => 
        array (
          'pretty_version' => '7.2.1',
          'version' => '7.2.1.0',
          'reference' => 'a5c75038693ad2e8d4b6c15ba2403532647830c4',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/environment',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/exporter' => 
        array (
          'pretty_version' => '6.3.2',
          'version' => '6.3.2.0',
          'reference' => '70a298763b40b213ec087c51c739efcaa90bcd74',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/exporter',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/global-state' => 
        array (
          'pretty_version' => '7.0.2',
          'version' => '7.0.2.0',
          'reference' => '3be331570a721f9a4b5917f4209773de17f747d7',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/global-state',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/lines-of-code' => 
        array (
          'pretty_version' => '3.0.1',
          'version' => '3.0.1.0',
          'reference' => 'd36ad0d782e5756913e42ad87cb2890f4ffe467a',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/lines-of-code',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/object-enumerator' => 
        array (
          'pretty_version' => '6.0.1',
          'version' => '6.0.1.0',
          'reference' => 'f5b498e631a74204185071eb41f33f38d64608aa',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/object-enumerator',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/object-reflector' => 
        array (
          'pretty_version' => '4.0.1',
          'version' => '4.0.1.0',
          'reference' => '6e1a43b411b2ad34146dee7524cb13a068bb35f9',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/object-reflector',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/recursion-context' => 
        array (
          'pretty_version' => '6.0.3',
          'version' => '6.0.3.0',
          'reference' => 'f6458abbf32a6c8174f8f26261475dc133b3d9dc',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/recursion-context',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/type' => 
        array (
          'pretty_version' => '5.1.3',
          'version' => '5.1.3.0',
          'reference' => 'f77d2d4e78738c98d9a68d2596fe5e8fa380f449',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/type',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'sebastian/version' => 
        array (
          'pretty_version' => '5.0.2',
          'version' => '5.0.2.0',
          'reference' => 'c687e3387b99f5b03b6caa64c74b63e2936ff874',
          'type' => 'library',
          'install_path' => '../../composer/../sebastian/version',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'spatie/once' => 
        array (
          'dev_requirement' => false,
          'replaced' => 
          array (
            0 => '*',
          ),
        ),
        'staabm/side-effects-detector' => 
        array (
          'pretty_version' => '1.0.5',
          'version' => '1.0.5.0',
          'reference' => 'd8334211a140ce329c13726d4a715adbddd0a163',
          'type' => 'library',
          'install_path' => '../../composer/../staabm/side-effects-detector',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'symfony/clock' => 
        array (
          'pretty_version' => 'v8.1.0',
          'version' => '8.1.0.0',
          'reference' => '701ef4de9705d6c32292ebee5e8044094a09fbf6',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/clock',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/console' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '962e18f09ebe68a49039b4c82fc0ea4871824fca',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/console',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/css-selector' => 
        array (
          'pretty_version' => 'v8.1.5',
          'version' => '8.1.5.0',
          'reference' => 'a291fb5adb65f52a4bb315db2d803698315dc64d',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/css-selector',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/deprecation-contracts' => 
        array (
          'pretty_version' => 'v3.7.1',
          'version' => '3.7.1.0',
          'reference' => 'f3202fa1b5097b0af062dc978b32ecf63404e31d',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/deprecation-contracts',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/error-handler' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '8373921e231e190a88e2ad526951bbaa791576fa',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/error-handler',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/event-dispatcher' => 
        array (
          'pretty_version' => 'v8.1.5',
          'version' => '8.1.5.0',
          'reference' => '7458da64220376b2e0dc2d8451bf43382c1ad297',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/event-dispatcher',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/event-dispatcher-contracts' => 
        array (
          'pretty_version' => 'v3.7.1',
          'version' => '3.7.1.0',
          'reference' => 'c7de7a00ffb67842132da02ea92988a39ccd9f4e',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/event-dispatcher-contracts',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/event-dispatcher-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '2.0|3.0',
          ),
        ),
        'symfony/finder' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '5ce28827081f6d1f0c32eaf3882750f19cb5bbe6',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/finder',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/http-foundation' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '2ebe78c083501dfb9509b31a7aedcae4d60a391f',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/http-foundation',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/http-kernel' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => 'aa160388d444210e3d01bbb4c5c53af4cd763df4',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/http-kernel',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/mailer' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => 'b17c9bf3a551d5f635638a3b6c05f06c4dc87584',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/mailer',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/mime' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => 'bf328d82105831db3e409195db0540ff57f27c80',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/mime',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-ctype' => 
        array (
          'pretty_version' => 'v1.37.0',
          'version' => '1.37.0.0',
          'reference' => '141046a8f9477948ff284fa65be2095baafb94f2',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-ctype',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-intl-grapheme' => 
        array (
          'pretty_version' => 'v1.41.0',
          'version' => '1.41.0.0',
          'reference' => 'bb899c1db0aa8127dc3afe8cda4a67eb24915f8d',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-intl-grapheme',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-intl-idn' => 
        array (
          'pretty_version' => 'v1.42.0',
          'version' => '1.42.0.0',
          'reference' => '51b5ff5ba85452b31ec6f55490b08148612339d9',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-intl-idn',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-intl-normalizer' => 
        array (
          'pretty_version' => 'v1.42.0',
          'version' => '1.42.0.0',
          'reference' => 'aa20edea75bd9c48cfecc8360922e5a6e5c44502',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-intl-normalizer',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-mbstring' => 
        array (
          'pretty_version' => 'v1.38.2',
          'version' => '1.38.2.0',
          'reference' => 'd3d318bad5e7a1bfbd026009c8bfb8d8f99ae6b6',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-mbstring',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-php80' => 
        array (
          'pretty_version' => 'v1.37.0',
          'version' => '1.37.0.0',
          'reference' => 'dfb55726c3a76ea3b6459fcfda1ec2d80a682411',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-php80',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-php83' => 
        array (
          'pretty_version' => 'v1.41.0',
          'version' => '1.41.0.0',
          'reference' => '5ea99087fb99c273a9b9236ed4c31e78b16103c6',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-php83',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-php84' => 
        array (
          'pretty_version' => 'v1.38.1',
          'version' => '1.38.1.0',
          'reference' => 'f4e1dfaee5b74aba5964fe1fd4dfc7ba5e3085fa',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-php84',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-php85' => 
        array (
          'pretty_version' => 'v1.41.0',
          'version' => '1.41.0.0',
          'reference' => '255fab485aaa1006ed411040c42aecd7b5302d7a',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-php85',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/polyfill-uuid' => 
        array (
          'pretty_version' => 'v1.37.0',
          'version' => '1.37.0.0',
          'reference' => '26dfec253c4cf3e51b541b52ddf7e42cb0908e94',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/polyfill-uuid',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/process' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '058d17fc284cce14efb2385783b55014a461b176',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/process',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/routing' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => 'ddd558991e98f693ae6bf5063cc1b0362c6bbec3',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/routing',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/service-contracts' => 
        array (
          'pretty_version' => 'v3.7.1',
          'version' => '3.7.1.0',
          'reference' => 'c0a284bab1ed8aa0417e3d69250ab437739563a0',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/service-contracts',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/string' => 
        array (
          'pretty_version' => 'v8.1.2',
          'version' => '8.1.2.0',
          'reference' => '286a76b7255e5cc4bf0101a0bc5388ecf1c38ccc',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/string',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/translation' => 
        array (
          'pretty_version' => 'v8.1.5',
          'version' => '8.1.5.0',
          'reference' => 'd9e1caba0d6b6f9a26710af8a2f88d37f001215a',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/translation',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/translation-contracts' => 
        array (
          'pretty_version' => 'v3.7.1',
          'version' => '3.7.1.0',
          'reference' => 'ccb206b98faccc511ebae8e5fad50f2dc0b30621',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/translation-contracts',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/translation-implementation' => 
        array (
          'dev_requirement' => false,
          'provided' => 
          array (
            0 => '2.3|3.0',
          ),
        ),
        'symfony/uid' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '69d732355a139c6f8881337d28515aa01f12b8be',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/uid',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/var-dumper' => 
        array (
          'pretty_version' => 'v7.4.17',
          'version' => '7.4.17.0',
          'reference' => '53712df8727da1744490202eeb9cb50d4b95419d',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/var-dumper',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'symfony/yaml' => 
        array (
          'pretty_version' => 'v8.1.5',
          'version' => '8.1.5.0',
          'reference' => 'b3fc9e8888eeb9daddc33bfbd15d282a61f543cd',
          'type' => 'library',
          'install_path' => '../../composer/../symfony/yaml',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'theseer/tokenizer' => 
        array (
          'pretty_version' => '1.3.1',
          'version' => '1.3.1.0',
          'reference' => 'b7489ce515e168639d17feec34b8847c326b0b3c',
          'type' => 'library',
          'install_path' => '../../composer/../theseer/tokenizer',
          'aliases' => 
          array (
          ),
          'dev_requirement' => true,
        ),
        'tijsverkoyen/css-to-inline-styles' => 
        array (
          'pretty_version' => 'v2.4.0',
          'version' => '2.4.0.0',
          'reference' => 'f0292ccf0ec75843d65027214426b6b163b48b41',
          'type' => 'library',
          'install_path' => '../../composer/../tijsverkoyen/css-to-inline-styles',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'vlucas/phpdotenv' => 
        array (
          'pretty_version' => 'v5.7.0',
          'version' => '5.7.0.0',
          'reference' => '301c07936b16d88628b126b01d082ba153cf4c40',
          'type' => 'library',
          'install_path' => '../../composer/../vlucas/phpdotenv',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
        'voku/portable-ascii' => 
        array (
          'pretty_version' => '2.1.1',
          'version' => '2.1.1.0',
          'reference' => '8e1051fe39379367aecf014f41744ce7539a856f',
          'type' => 'library',
          'install_path' => '../../composer/../voku/portable-ascii',
          'aliases' => 
          array (
          ),
          'dev_requirement' => false,
        ),
      ),
    ),
  ),
  'executedFilesHashes' => 
  array (
    '../../../phpstan-bootstrap.php' => '2ea674a3fbb6d164715ec47a326dcf91ddd718e6400d36f476a036b0ddfd762d',
    '../../larastan/larastan/bootstrap.php' => '5a3eacbf63b3e41659adfee92facededf8e020a932800f93c9a8b0e67f235805',
    'phar://phpstan.phar/stubs/runtime/Attribute85.php' => 'cb8b31e82c61ce197871c9e8a6f122256751f2ab606dd2be90846d4fa5f8933e',
    'phar://phpstan.phar/stubs/runtime/ReflectionAttribute.php' => 'c0068e383717870a304781d462f7e2afe1c6f24e9133851852a2aca96b4fa26f',
    'phar://phpstan.phar/stubs/runtime/ReflectionIntersectionType.php' => '65fe0a8bc6fe285d8ddc8798ab5b9299920af70db5ad74596bc08df823e7c5d9',
    'phar://phpstan.phar/stubs/runtime/ReflectionUnionType.php' => '1e2fe940e4ba4e00d9ee6adb2af3ee1bf333e6f8afe61c61deb038886d293427',
  ),
  'phpExtensions' => 
  array (
    0 => 'Core',
    1 => 'PDO',
    2 => 'Phar',
    3 => 'Reflection',
    4 => 'SPL',
    5 => 'SimpleXML',
    6 => 'Zend OPcache',
    7 => 'bcmath',
    8 => 'calendar',
    9 => 'ctype',
    10 => 'curl',
    11 => 'date',
    12 => 'dom',
    13 => 'fileinfo',
    14 => 'filter',
    15 => 'gd',
    16 => 'hash',
    17 => 'iconv',
    18 => 'json',
    19 => 'lexbor',
    20 => 'libxml',
    21 => 'mbstring',
    22 => 'mysqli',
    23 => 'mysqlnd',
    24 => 'odbc',
    25 => 'openssl',
    26 => 'pcre',
    27 => 'pdo_mysql',
    28 => 'pdo_pgsql',
    29 => 'pdo_sqlite',
    30 => 'pgsql',
    31 => 'random',
    32 => 'readline',
    33 => 'session',
    34 => 'soap',
    35 => 'sockets',
    36 => 'sodium',
    37 => 'sqlite3',
    38 => 'standard',
    39 => 'tokenizer',
    40 => 'uri',
    41 => 'xml',
    42 => 'xmlreader',
    43 => 'xmlwriter',
    44 => 'zip',
    45 => 'zlib',
  ),
  'stubFiles' => 
  array (
  ),
  'level' => '6',
),
	'projectExtensionFiles' => array (
),
	'errorsCallback' => static function (): array { return array (
  '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Parameter #1 $view of function view expects view-string|null, string given.',
       'file' => '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php',
       'line' => 21,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 21,
       'nodeType' => 'PhpParser\\Node\\Expr\\FuncCall',
       'identifier' => 'argument.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Parameter #1 $view of function view expects view-string|null, string given.',
       'file' => '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php',
       'line' => 21,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 21,
       'nodeType' => 'PhpParser\\Node\\Expr\\FuncCall',
       'identifier' => 'argument.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
  '../../../app/Http/Controllers/Auth/NewPasswordController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Parameter #1 $view of function view expects view-string|null, string given.',
       'file' => '../../../app/Http/Controllers/Auth/NewPasswordController.php',
       'line' => 21,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/Auth/NewPasswordController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 21,
       'nodeType' => 'PhpParser\\Node\\Expr\\FuncCall',
       'identifier' => 'argument.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
  '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Parameter #1 $view of function view expects view-string|null, string given.',
       'file' => '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php',
       'line' => 18,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 18,
       'nodeType' => 'PhpParser\\Node\\Expr\\FuncCall',
       'identifier' => 'argument.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
  '../../../app/Http/Controllers/Auth/VerifyEmailController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Parameter #1 $user of class Illuminate\\Auth\\Events\\Verified constructor expects Illuminate\\Contracts\\Auth\\MustVerifyEmail, App\\Models\\User|null given.',
       'file' => '../../../app/Http/Controllers/Auth/VerifyEmailController.php',
       'line' => 25,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/Auth/VerifyEmailController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 25,
       'nodeType' => 'PhpParser\\Node\\Expr\\New_',
       'identifier' => 'argument.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
  '../../../app/Http/Controllers/RoutingController.php' => 
  array (
    0 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::index() should return Illuminate\\Http\\Response but returns Illuminate\\Http\\RedirectResponse.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 31,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 31,
       'nodeType' => 'PhpParser\\Node\\Stmt\\Return_',
       'identifier' => 'return.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    1 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::index() should return Illuminate\\Http\\Response but returns Illuminate\\Http\\RedirectResponse.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 33,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 33,
       'nodeType' => 'PhpParser\\Node\\Stmt\\Return_',
       'identifier' => 'return.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    2 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::root() has parameter $first with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 42,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 42,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    3 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::root() should return Illuminate\\Http\\Response but returns Illuminate\\Contracts\\View\\Factory|Illuminate\\View\\View.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 44,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 44,
       'nodeType' => 'PhpParser\\Node\\Stmt\\Return_',
       'identifier' => 'return.type',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    4 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::secondLevel() has no return type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 50,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 50,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.return',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    5 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::secondLevel() has parameter $first with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 50,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 50,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    6 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::secondLevel() has parameter $second with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 50,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 50,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    7 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::thirdLevel() has no return type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 58,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 58,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.return',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    8 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::thirdLevel() has parameter $first with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 58,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 58,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    9 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::thirdLevel() has parameter $second with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 58,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 58,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
    10 => 
    \PHPStan\Analyser\Error::__set_state(array(
       'message' => 'Method App\\Http\\Controllers\\RoutingController::thirdLevel() has parameter $third with no type specified.',
       'file' => '../../../app/Http/Controllers/RoutingController.php',
       'line' => 58,
       'canBeIgnored' => true,
       'filePath' => '../../../app/Http/Controllers/RoutingController.php',
       'traitFilePath' => NULL,
       'tip' => NULL,
       'nodeLine' => 58,
       'nodeType' => 'PHPStan\\Node\\InClassMethodNode',
       'identifier' => 'missingType.parameter',
       'metadata' => 
      array (
      ),
       'fixedErrorDiff' => NULL,
    )),
  ),
); },
	'locallyIgnoredErrorsCallback' => static function (): array { return array (
); },
	'linesToIgnore' => array (
),
	'unmatchedLineIgnores' => array (
),
	'collectedDataCallback' => static function (): array { return array (
  '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.login',
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController',
        1 => 'create',
        2 => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedTranslationFunctionCollector' => 
    array (
      0 => 
      array (
        0 => 'auth.password',
        1 => 37,
      ),
    ),
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.confirm-password',
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Auth::guard(\'web\')->validate([\'email\' => $request->user()->email, \'password\' => $request->password]):32',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController',
        1 => 'show',
        2 => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$request->user()->hasVerifiedEmail():19',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.verify-email',
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$request->user()->hasVerifiedEmail():19',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/NewPasswordController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.forgot-password',
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\Auth\\NewPasswordController',
        1 => 'create',
        2 => 'App\\Http\\Controllers\\Auth\\NewPasswordController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'event',
        1 => 51,
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.forgot-password',
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController',
        1 => 'create',
        2 => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/RegisteredUserController.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedViewFunctionCollector' => 
    array (
      0 => 'auth.signup',
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\Auth\\RegisteredUserController',
        1 => 'create',
        2 => 'App\\Http\\Controllers\\Auth\\RegisteredUserController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'event',
        1 => 47,
      ),
    ),
  ),
  '../../../app/Http/Controllers/Auth/VerifyEmailController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$request->user()->hasVerifiedEmail():20',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$request->user()->markEmailAsVerified():24',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'event',
        1 => 25,
      ),
    ),
  ),
  '../../../app/Http/Controllers/RoutingController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Auth::user():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\RoutingController',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Controllers\\RoutingController',
        1 => 'root',
        2 => 'App\\Http\\Controllers\\RoutingController',
        3 => 
        array (
          0 => 'f' . "\0" . 'view',
        ),
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
      ),
    ),
  ),
  '../../../app/Http/Requests/Auth/LoginRequest.php' => 
  array (
    'Larastan\\Larastan\\Collectors\\UsedTranslationFunctionCollector' => 
    array (
      0 => 
      array (
        0 => 'auth.failed',
        1 => 50,
      ),
      1 => 
      array (
        0 => 'auth.throttle',
        1 => 75,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Auth::attempt($this->only(\'email\', \'password\'), $this->filled(\'remember\')):46',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\RateLimiter::tooManyAttempts($this->throttleKey(), 5):66',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Http\\Requests\\Auth\\LoginRequest',
        1 => 'authorize',
        2 => 'App\\Http\\Requests\\Auth\\LoginRequest',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Http\\Requests\\Auth\\LoginRequest',
        1 => 'rules',
        2 => 'App\\Http\\Requests\\Auth\\LoginRequest',
        3 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'event',
        1 => 70,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Support\\Facades\\RateLimiter',
        1 => 'hit',
        2 => 47,
      ),
    ),
  ),
  '../../../app/Models/User.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Models\\User',
        1 => 'casts',
        2 => 'App\\Models\\User',
        3 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Notifications\\Notifiable',
      ),
    ),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($request->query(\'status\')):26',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\App\\Modules\\Catalog\\Models\\CatalogItem::query()->where(\'sku\', $validated[\'sku\'])->exists():65',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Catalog\\Http\\Controllers\\CatalogItemController',
        1 => 'serialize',
        2 => 'App\\Modules\\Catalog\\Http\\Controllers\\CatalogItemController',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'getkey',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 81,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 105,
      ),
      2 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 137,
      ),
      3 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 161,
      ),
    ),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/PriceController.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 60,
      ),
    ),
  ),
  '../../../app/Modules/Catalog/Models/CatalogItem.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        1 => 'newFactory',
        2 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        1 => 'casts',
        2 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Catalog\\Models\\CatalogItem',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
    ),
  ),
  '../../../app/Modules/Catalog/Models/CatalogPrice.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        1 => 'casts',
        2 => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 44,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($application->status, [\\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_DRAFT, \\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_UNDER_REVIEW], true):54',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($step, $stepIds, true):62',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 87,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 149,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Contracts\\Filesystem\\Filesystem',
        1 => 'put',
        2 => 139,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($application->status, $allowed, true):122',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
        1 => 'approve',
        2 => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\http\\controllers\\reviewcontroller' . "\0" . 'decide',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
        1 => 'requestInfo',
        2 => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\http\\controllers\\reviewcontroller' . "\0" . 'decide',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 109,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 113,
      ),
      2 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 135,
      ),
      3 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 150,
      ),
      4 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Audit\\AuditWriter',
        ),
        1 => 'record',
        2 => 152,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($application->status, [\\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_DRAFT, \\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_WITHDRAWN], true):88',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($application->status, [\\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_DRAFT, \\App\\Modules\\Onboarding\\Models\\PartnerApplication::STATUS_WITHDRAWN], true):126',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
        1 => 'missingSteps',
        2 => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\trackcatalog' . "\0" . 'find',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
        1 => 'nowIso',
        2 => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
        3 => 
        array (
          0 => 'f' . "\0" . 'gmdate',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 151,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Models/ApplicationDocument.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Models/OtpChallenge.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        1 => 'casts',
        2 => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Models/PartnerApplication.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        1 => 'newFactory',
        2 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        1 => 'casts',
        2 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/Models/ReviewRecord.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        1 => 'getConnectionName',
        2 => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        1 => 'seed',
        2 => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . '__construct',
          1 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'forcefill',
          2 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'save',
          3 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'getkey',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 36,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/OtpService.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'hash_equals($expected, $actual):80',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\OtpService',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 50,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'delete',
        2 => 70,
      ),
      2 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 75,
      ),
      3 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'delete',
        2 => 84,
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/RandomOtpCodes.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\RandomOtpCodes',
        1 => 'lastFor',
        2 => 'App\\Modules\\Onboarding\\RandomOtpCodes',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/SeededOtpCodes.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\SeededOtpCodes',
        1 => 'lastFor',
        2 => 'App\\Modules\\Onboarding\\SeededOtpCodes',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/StepValidators.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):39',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($name):43',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($phone):47',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):62',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):74',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($item):78',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):91',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):104',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($gstin):108',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($bank):112',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):124',
        3 => NULL,
      ),
      11 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_int($slots):128',
        3 => NULL,
      ),
      12 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($slots):128',
        3 => NULL,
      ),
      13 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'ctype_digit($slots):128',
        3 => NULL,
      ),
      14 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):143',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validate',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validateidentity',
          1 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validatecertifications',
          2 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validateidlist',
          3 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validateavailability',
          4 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validatebusiness',
          5 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validatecapacity',
          6 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validateagreement',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateIdentity',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
          0 => 'f' . "\0" . 'preg_match',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateCertifications',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\stepvalidators' . "\0" . 'validateidlist',
        ),
      ),
      3 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateIdList',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
        ),
      ),
      4 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateAvailability',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
        ),
      ),
      5 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateBusiness',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
          0 => 'f' . "\0" . 'preg_match',
        ),
      ),
      6 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateCapacity',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
        ),
      ),
      7 => 
      array (
        0 => 'App\\Modules\\Onboarding\\StepValidators',
        1 => 'validateAgreement',
        2 => 'App\\Modules\\Onboarding\\StepValidators',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Modules/Onboarding/TrackCatalog.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Modules\\Onboarding\\TrackCatalog',
        1 => 'tracks',
        2 => 'App\\Modules\\Onboarding\\TrackCatalog',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Modules\\Onboarding\\TrackCatalog',
        1 => 'find',
        2 => 'App\\Modules\\Onboarding\\TrackCatalog',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\modules\\onboarding\\trackcatalog' . "\0" . 'tracks',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Api/CodedHttpException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Api\\CodedHttpException',
        1 => 
        array (
          0 => 'm' . "\0" . 'symfony\\component\\httpkernel\\exception\\httpexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        1 => '__construct',
        2 => 19,
      ),
    ),
  ),
  '../../../app/Platform/Api/Console/ApiComposeCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_file($kernelPath):26',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => '$file->isFile():38',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'preg_match(\'/^openapi.*\\.yaml$/\', $file->getFilename()):38',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'str_contains($file->getPath(), DIRECTORY_SEPARATOR . \'Http\'):38',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->option(\'check\'):55',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_file($out):57',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Api/CursorPagination.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_numeric($limitInput):26',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($cursor):68',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($payload):80',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($payload[\'created_at\']):82',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($payload[\'id\']):83',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Api\\CursorPagination',
        1 => 'decodeCursor',
        2 => 'App\\Platform\\Api\\CursorPagination',
        3 => 
        array (
          0 => 'f' . "\0" . 'json_decode',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Api/ErrorEnvelope.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$request->expectsJson():38',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$request->is(\'api/*\'):38',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$e->getMessage():88',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$e->getMessage():137',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$e->getMessage():141',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\App\\Platform\\Api\\ResponseMessages::reasonPhrase($status):182',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Api\\ErrorEnvelope',
        1 => 'codeForStatus',
        2 => 'App\\Platform\\Api\\ErrorEnvelope',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\support\\str' . "\0" . 'snake',
          1 => 'm' . "\0" . 'app\\platform\\api\\responsemessages' . "\0" . 'reasonphrase',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Api/OpenApiComposer.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($document):42',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($document[\'paths\']):42',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_array($operations):54',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($operation):56',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_string($operationId):60',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_string($required):68',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($code, $declaredPermissions, true):77',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($operations):83',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($operation):87',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($operation[\'x-gate\'] ?? null, self::PUBLIC_GATES, true):90',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_array($parsed):123',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'ksort',
        1 => 100,
      ),
    ),
  ),
  '../../../app/Platform/Api/ResponseMessages.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Api\\ResponseMessages',
        1 => 'forStatus',
        2 => 'App\\Platform\\Api\\ResponseMessages',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Audit/AuditLog.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Audit\\AuditLog',
        1 => 'getConnectionName',
        2 => 'App\\Platform\\Audit\\AuditLog',
        3 => 
        array (
          0 => 'f' . "\0" . 'app',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'connectionname',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Audit\\AuditLog',
        1 => 'casts',
        2 => 'App\\Platform\\Audit\\AuditLog',
        3 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Audit/AuditWriter.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():35',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($requestId):109',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Audit\\AuditWriter',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/AuthorizationDecision.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($decision):84',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($stage):84',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($values):107',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):114',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($stages):127',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_array($stage):134',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationDecision',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationDecision',
        1 => 'stringList',
        2 => 'App\\Platform\\Authz\\AuthorizationDecision',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationDecision',
        1 => 'stageList',
        2 => 'App\\Platform\\Authz\\AuthorizationDecision',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationDecision',
        1 => 'allowed',
        2 => 'App\\Platform\\Authz\\AuthorizationDecision',
        3 => 
        array (
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationDecision',
        1 => 'toArray',
        2 => 'App\\Platform\\Authz\\AuthorizationDecision',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/AuthorizationRequest.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthorizationRequest',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/AuthzCacheInvalidator.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/CapabilityAuthorizer.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():31',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->allows($user, $capability):59',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\CapabilityAuthorizer',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/ConditionEvaluator.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($condition[\'field\'] ?? null):25',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($condition[\'operator\'] ?? null):26',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($field, \\App\\Platform\\Authz\\Contracts\\Condition::FIELDS, true):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($operator, \\App\\Platform\\Authz\\Contracts\\Condition::OPERATORS, true):29',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'value\', $condition):30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists($field, $attributes):36',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->compare($field, $operator, $attributes[$field], $condition[\'value\']):42',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($expected):53',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_is_list($expected):53',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->compareScalar($field, \'eq\', $actual, $item):60',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->isNumeric($actual):76',
        3 => NULL,
      ),
      11 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->isNumeric($expected):76',
        3 => NULL,
      ),
      12 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($actual):84',
        3 => NULL,
      ),
      13 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($expected):84',
        3 => NULL,
      ),
      14 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($actual):101',
        3 => NULL,
      ),
      15 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($expected):101',
        3 => NULL,
      ),
      16 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => 'is_int($value):127',
        3 => NULL,
      ),
      17 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => 'is_float($value):127',
        3 => NULL,
      ),
      18 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):127',
        3 => NULL,
      ),
      19 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_numeric($value):127',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\ConditionEvaluator',
        1 => 'evaluate',
        2 => 'App\\Platform\\Authz\\ConditionEvaluator',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\conditionevaluator' . "\0" . 'compare',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\ConditionEvaluator',
        1 => 'compare',
        2 => 'App\\Platform\\Authz\\ConditionEvaluator',
        3 => 
        array (
          0 => 'f' . "\0" . 'array_is_list',
          1 => 'm' . "\0" . 'app\\platform\\authz\\conditionevaluator' . "\0" . 'comparescalar',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\ConditionEvaluator',
        1 => 'compareScalar',
        2 => 'App\\Platform\\Authz\\ConditionEvaluator',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\conditionevaluator' . "\0" . 'isnumeric',
          1 => 'm' . "\0" . 'app\\platform\\authz\\conditionevaluator' . "\0" . 'comparenumbers',
          2 => 'f' . "\0" . 'strtotime',
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Authz\\ConditionEvaluator',
        1 => 'compareNumbers',
        2 => 'App\\Platform\\Authz\\ConditionEvaluator',
        3 => 
        array (
        ),
      ),
      4 => 
      array (
        0 => 'App\\Platform\\Authz\\ConditionEvaluator',
        1 => 'isNumeric',
        2 => 'App\\Platform\\Authz\\ConditionEvaluator',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Console/AuthzExplainCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):98',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        1 => 'attributes',
        2 => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'option',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$tenants->isEmpty():75',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($capability->action, $actions, true):128',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_array($selector):169',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$registry->has($parsed->module):181',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$tenants->isEmpty():207',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        1 => 'collectDeclaredCapabilities',
        2 => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'modules',
          1 => 'm' . "\0" . 'app\\platform\\authz\\capabilityparser' . "\0" . 'parse',
          2 => 'f' . "\0" . 'ksort',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'ksort',
        1 => 150,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 324,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'delete',
        2 => 328,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Builder',
        1 => 'create',
        2 => 313,
      ),
    ),
  ),
  '../../../app/Platform/Authz/Contracts/Capability.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\Capability',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\Capability',
        1 => 'toArray',
        2 => 'App\\Platform\\Authz\\Contracts\\Capability',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Contracts/CapabilitySelector.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):54',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
        1 => 'matches',
        2 => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
        1 => 'toArray',
        2 => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Contracts/Condition.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($field):45',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($field, self::FIELDS, true):45',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($operator):52',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($operator, self::OPERATORS, true):52',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'value\', $condition):59',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):74',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_is_list($value):74',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_int($value):95',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_float($value):95',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):95',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_numeric($value):95',
        3 => NULL,
      ),
      11 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):102',
        3 => NULL,
      ),
      12 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):109',
        3 => NULL,
      ),
      13 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($value):117',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\Condition',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\Condition',
        1 => 'toArray',
        2 => 'App\\Platform\\Authz\\Contracts\\Condition',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Contracts/PolicyStatus.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
        1 => 'allowedTransitions',
        2 => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
        1 => 'canTransitionTo',
        2 => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\contracts\\policystatus' . "\0" . 'allowedtransitions',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Contracts/ScopeBinding.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($scopeType):44',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($scopeId):48',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($scopeMode):52',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($scopeMode, self::SCOPE_MODES, true):52',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        1 => 'toArray',
        2 => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/DecisionCache.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($payload):37',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_array($value):103',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\DecisionCache',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\DecisionCache',
        1 => 'key',
        2 => 'App\\Platform\\Authz\\DecisionCache',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\decisioncache' . "\0" . 'ksortrecursive',
          1 => 'm' . "\0" . 'app\\platform\\authz\\decisioncache' . "\0" . 'generation',
          2 => 'f' . "\0" . 'json_encode',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\DecisionCache',
        1 => 'generationKey',
        2 => 'App\\Platform\\Authz\\DecisionCache',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\DecisionCache',
        1 => 'ttl',
        2 => 'App\\Platform\\Authz\\DecisionCache',
        3 => 
        array (
          0 => 'f' . "\0" . 'config',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'ksort',
        1 => 100,
      ),
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyCreated.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyCreated',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyCreated',
        1 => 'platformEventName',
        2 => 'App\\Platform\\Authz\\Events\\PolicyCreated',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyStatusChanged.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
        1 => 'platformEventName',
        2 => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyUpdated.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        1 => 'platformEventName',
        2 => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/AuthorizationDeniedException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
        1 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\auth\\access\\authorizationexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Auth\\Access\\AuthorizationException',
        1 => '__construct',
        2 => 23,
      ),
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/CapabilityParseException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/InvalidPolicyPayloadException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_array($queryInput):43',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists($field, $validated):71',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/PolicyController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($status):42',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($effect):52',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Http\\Controllers\\PolicyController',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/RoleController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$context->isResolved():53',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\App\\Platform\\Authz\\Models\\Role::query()->where(\'code\', $validated[\'code\'])->exists():115',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 127,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'delete',
        2 => 212,
      ),
    ),
  ),
  '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():56',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Http\\Middleware\\CheckCapability',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        ),
        1 => 'ensure',
        2 => 41,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Audit\\AuditWriter',
        ),
        1 => 'record',
        2 => 62,
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/CapabilityMetadata.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        1 => 'casts',
        2 => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
        3 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        1 => 'role',
        2 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'belongsto',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        1 => 'scopeBindings',
        2 => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'hasmany',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/Policy.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Policy',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\Policy',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Policy',
        1 => 'casts',
        2 => 'App\\Platform\\Authz\\Models\\Policy',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Policy',
        1 => 'includesPlatformDefaults',
        2 => 'App\\Platform\\Authz\\Models\\Policy',
        3 => 
        array (
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Policy',
        1 => 'isPlatformDefault',
        2 => 'App\\Platform\\Authz\\Models\\Policy',
        3 => 
        array (
        ),
      ),
      4 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Policy',
        1 => 'toPayload',
        2 => 'App\\Platform\\Authz\\Models\\Policy',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'getkey',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/Role.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Role',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\Role',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Role',
        1 => 'permissions',
        2 => 'App\\Platform\\Authz\\Models\\Role',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'hasmany',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\Role',
        1 => 'assignments',
        2 => 'App\\Platform\\Authz\\Models\\Role',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'hasmany',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/RolePermission.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\RolePermission',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\RolePermission',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\RolePermission',
        1 => 'role',
        2 => 'App\\Platform\\Authz\\Models\\RolePermission',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'belongsto',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/Models/RoleScopeBinding.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\ConstantConditionInTraitCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getAttributes():24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeFunctionCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'app(\\App\\Platform\\Tenancy\\TenantContext::class):28',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '!$context->isResolved():30',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->getTable():31',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->tenantId():34',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$model->setAttribute(\'tenant_id\', $context->tenantId()):34',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::creating(function (\\Illuminate\\Database\\Eloquent\\Model $model): void {
    if (array_key_exists(\'tenant_id\', $model->getAttributes())) {
        return;
    }
    $context = app(\\App\\Platform\\Tenancy\\TenantContext::class);
    if (!$context->isResolved()) {
        throw new \\App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException(\'creating \' . $model->getTable());
    }
    $model->setAttribute(\'tenant_id\', $context->tenantId());
}):23',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\ImpossibleCheckTypeStaticMethodCallRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'static::addGlobalScope(new \\App\\Platform\\Tenancy\\TenantScope()):37',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => 'array_key_exists(\'tenant_id\', $model->getAttributes()):24',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        2 => '$context->isResolved():30',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        1 => 'newFactory',
        2 => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        1 => 'assignment',
        2 => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'belongsto',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Model',
        1 => 'addGlobalScope',
        2 => 37,
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      2 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Authz/PolicyEvaluationEngine.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$decision->allowed():121',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => '$this->grantExists($membershipId, $capability->code):183',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():207',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_array($selectorPayload):226',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$selector->matches($capability->module, $capability->resource, $capability->action):238',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():293',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => '$this->isSensitive($capability->code):316',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        1 => 'evaluate',
        2 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'membershipid',
          1 => 'm' . "\0" . 'app\\platform\\tenancy\\tenantcontext' . "\0" . 'tenantid',
          2 => 'm' . "\0" . 'app\\platform\\authz\\decisioncache' . "\0" . 'get',
          3 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'runchain',
          4 => 'm' . "\0" . 'app\\platform\\authz\\decisioncache' . "\0" . 'put',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        1 => 'runChain',
        2 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'principalid',
          1 => 'm' . "\0" . 'app\\platform\\authz\\capabilityparser' . "\0" . 'parse',
          2 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'stage',
          3 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'decision',
          4 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'matchingactivepolicies',
          5 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'getkey',
          6 => 'f' . "\0" . 'array_map',
          7 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'explicitdenystage',
          8 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'securityconstraintstage',
          9 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'policyconstraintstage',
          10 => 'm' . "\0" . 'app\\platform\\authz\\policyevaluationengine' . "\0" . 'grantexists',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        1 => 'stage',
        2 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        3 => 
        array (
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        1 => 'decision',
        2 => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\authz\\authorizationdecision' . "\0" . '__construct',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Authz/PolicyService.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->nameTaken($payload[\'name\']):44',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => '$this->nameTaken($payload[\'name\'], (string) $policy->getKey()):92',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$from->canTransitionTo($target):128',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->context->isResolved():161',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$policy->isPlatformDefault():180',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\PolicyService',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'event',
        1 => 74,
      ),
      1 => 
      array (
        0 => 'event',
        1 => 117,
      ),
      2 => 
      array (
        0 => 'event',
        1 => 152,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Audit\\AuditWriter',
        ),
        1 => 'record',
        2 => 64,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 104,
      ),
      2 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Audit\\AuditWriter',
        ),
        1 => 'record',
        2 => 106,
      ),
      3 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 139,
      ),
      4 => 
      array (
        0 => 
        array (
          0 => 'App\\Platform\\Audit\\AuditWriter',
        ),
        1 => 'record',
        2 => 141,
      ),
    ),
  ),
  '../../../app/Platform/Authz/PolicyValidator.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists($required, $input):46',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'name\', $input):101',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($name):104',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'description\', $input):111',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($description):114',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'effect\', $input):121',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($input[\'effect\']):122',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'capability_selector\', $input):131',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($input[\'capability_selector\']):132',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'scope_bindings\', $input):139',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'conditions\', $input):143',
        3 => NULL,
      ),
      11 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'array_key_exists(\'priority\', $input):147',
        3 => NULL,
      ),
      12 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_int($priority):150',
        3 => NULL,
      ),
      13 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($bindings):171',
        3 => NULL,
      ),
      14 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_is_list($bindings):171',
        3 => NULL,
      ),
      15 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($binding):182',
        3 => NULL,
      ),
      16 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($conditions):203',
        3 => NULL,
      ),
      17 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'array_is_list($conditions):203',
        3 => NULL,
      ),
      18 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($condition):214',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Authz/PrincipalResolver.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($guard):37',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($provider):39',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($provider):43',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Authz\\PrincipalResolver',
        1 => 'providerName',
        2 => 'App\\Platform\\Authz\\PrincipalResolver',
        3 => 
        array (
          0 => 'f' . "\0" . 'config',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Console/CleanupExpiredCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($tenant):42',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Console/DoctorCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'extension_loaded($ext):39',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'extension_loaded($ext):45',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'extension_loaded(\'redis\'):72',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->option(\'json\'):106',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Console\\DoctorCommand',
        1 => 'handle',
        2 => 'App\\Platform\\Console\\DoctorCommand',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\console\\doctorcommand' . "\0" . 'runchecks',
          1 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'option',
          2 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'line',
          3 => 'f' . "\0" . 'json_encode',
          4 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'table',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Http/Middleware/AttachRequestId.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Http\\Middleware\\AttachRequestId',
        1 => 'sanitize',
        2 => 'App\\Platform\\Http\\Middleware\\AttachRequestId',
        3 => 
        array (
          0 => 'f' . "\0" . 'preg_match',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Http/Middleware/LimitJsonBody.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'str_contains($contentType, \'application/json\'):23',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Identity/ActorTokenVerifier.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'hash_equals($expected, $signature):35',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($decoded):40',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($claims):44',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($userId):54',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($sessionId):54',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($expiresAt):54',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => 'str_contains($field, \'.\'):61',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => 'str_contains($field, \',\'):61',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($claims[\'r\']):67',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($claims[\'m\']):71',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($claims[\'o\']):72',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Identity\\ActorTokenVerifier',
        1 => 'verify',
        2 => 'App\\Platform\\Identity\\ActorTokenVerifier',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\identity\\actortokenverifier' . "\0" . 'base64urlencode',
          1 => 'f' . "\0" . 'json_decode',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Identity\\ActorTokenVerifier',
        1 => 'base64UrlEncode',
        2 => 'App\\Platform\\Identity\\ActorTokenVerifier',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/IdentitySelfServiceController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$request->hasSession():70',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$request->hasSession():92',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/InviteController.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$context->isResolved():70',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => '$invite->expires_at->isPast():139',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$context->isResolved():156',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'filter_var($invite->identifier, FILTER_VALIDATE_EMAIL):179',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'app()->bound(\\App\\Platform\\Identity\\Contracts\\TrackSeeder::class):257',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 114,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 182,
      ),
      2 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 203,
      ),
      3 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 244,
      ),
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Identity\\Http\\Controllers\\UserAdminController',
        1 => 'suspend',
        2 => 'App\\Platform\\Identity\\Http\\Controllers\\UserAdminController',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\identity\\http\\controllers\\useradmincontroller' . "\0" . 'setstatus',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Identity\\Http\\Controllers\\UserAdminController',
        1 => 'activate',
        2 => 'App\\Platform\\Identity\\Http\\Controllers\\UserAdminController',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\identity\\http\\controllers\\useradmincontroller' . "\0" . 'setstatus',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'is_string($header):30',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => 'str_starts_with($header, \'Bearer \'):30',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Identity/Models/Invite.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Identity\\Models\\Invite',
        1 => 'newFactory',
        2 => 'App\\Platform\\Identity\\Models\\Invite',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Identity\\Models\\Invite',
        1 => 'casts',
        2 => 'App\\Platform\\Identity\\Models\\Invite',
        3 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Modules/Console/ModuleListCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->option(\'json\'):22',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$registry->enabled($module):30',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '$registry->enabled($module):47',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
        1 => 'handle',
        2 => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'modules',
          1 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'option',
          2 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'line',
          3 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'enabled',
          4 => 'f' . "\0" . 'array_map',
          5 => 'f' . "\0" . 'json_encode',
          6 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'table',
          7 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'newline',
          8 => 'm' . "\0" . 'illuminate\\console\\command' . "\0" . 'info',
          9 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'permissions',
          10 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'events',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Modules/ManifestValidator.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($manifest[$required]):32',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_int($manifest[\'phase\']):55',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_int($manifest[\'priority\']):59',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($dependency):84',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($permission):102',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'str_starts_with($permissionCode, $code . \':\'):110',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array($action, $actions, true):128',
        3 => NULL,
      ),
      7 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($scope):145',
        3 => NULL,
      ),
      8 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'str_starts_with($scope, $code . \':\'):145',
        3 => NULL,
      ),
      9 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($scope):146',
        3 => NULL,
      ),
      10 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($event):164',
        3 => NULL,
      ),
      11 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'str_starts_with($name, $code . \'.\'):178',
        3 => NULL,
      ),
      12 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($event[\'schema\']):182',
        3 => NULL,
      ),
      13 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($entry):203',
        3 => NULL,
      ),
      14 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'in_array((string) $entry[\'permission\'], $declared, true):209',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ManifestValidator',
        1 => 'validateDependencies',
        2 => 'App\\Platform\\Modules\\ManifestValidator',
        3 => 
        array (
          0 => 'f' . "\0" . 'preg_match',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Modules\\ManifestValidator',
        1 => 'validateScopes',
        2 => 'App\\Platform\\Modules\\ManifestValidator',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Modules/ModuleManifest.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleManifest',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleManifest',
        1 => 'providerClass',
        2 => 'App\\Platform\\Modules\\ModuleManifest',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\support\\str' . "\0" . 'studly',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleManifest',
        1 => 'routesPath',
        2 => 'App\\Platform\\Modules\\ModuleManifest',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Modules/ModuleRegistry.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'class_exists($provider):77',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->app->bound(\'platform.feature_flags\'):196',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_dir($modulesPath):211',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'glob($modulesPath . DIRECTORY_SEPARATOR . \'*\'):217',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_dir($directory):218',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_file($manifestPath):224',
        3 => NULL,
      ),
      6 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($manifest):230',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 
        array (
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'modules',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'boot',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'find',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'boot',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'has',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'find',
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'permissions',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'boot',
          1 => 'f' . "\0" . 'ksort',
        ),
      ),
      4 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'scopes',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'boot',
          1 => 'f' . "\0" . 'sort',
        ),
      ),
      5 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'events',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\platform\\modules\\moduleregistry' . "\0" . 'boot',
          1 => 'f' . "\0" . 'sort',
        ),
      ),
      6 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleRegistry',
        1 => 'kernelManifest',
        2 => 'App\\Platform\\Modules\\ModuleRegistry',
        3 => 
        array (
          0 => 'f' . "\0" . 'config',
          1 => 'm' . "\0" . 'app\\platform\\modules\\modulemanifest' . "\0" . 'fromarray',
          2 => 'f' . "\0" . 'app_path',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'usort',
        1 => 53,
      ),
      1 => 
      array (
        0 => 'ksort',
        1 => 135,
      ),
      2 => 
      array (
        0 => 'sort',
        1 => 157,
      ),
      3 => 
      array (
        0 => 'sort',
        1 => 179,
      ),
    ),
  ),
  '../../../app/Platform/Modules/ModuleServiceProvider.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => 'is_dir($path):62',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleServiceProvider',
        1 => 'code',
        2 => 'App\\Platform\\Modules\\ModuleServiceProvider',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\support\\str' . "\0" . 'snake',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Modules\\ModuleServiceProvider',
        1 => 'modulePath',
        2 => 'App\\Platform\\Modules\\ModuleServiceProvider',
        3 => 
        array (
          0 => 'm' . "\0" . 'reflectionclass' . "\0" . '__construct',
        ),
      ),
    ),
  ),
  '../../../app/Platform/Observability/Health/ReadinessCheck.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'app()->bound(\'platform.outbox_heartbeat\'):94',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/BelongsToTenant.php' => 
  array (
    'PHPStan\\Rules\\Traits\\TraitDeclarationCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 19,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/ConnectionResolver.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->isRegistered($name):39',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($ciphertext):72',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_array($config):82',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\ConnectionResolver',
        1 => 'cachedFor',
        2 => 'App\\Platform\\Tenancy\\ConnectionResolver',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'getkey',
          1 => 'm' . "\0" . 'illuminate\\cache\\repository' . "\0" . 'remember',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Cache\\Repository',
        1 => 'forget',
        2 => 58,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 93,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 101,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'config()->has("database.connections.{$target}"):43',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 64,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantProvisionSchemaCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'config()->has("database.connections.{$connection}"):31',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_array($table):44',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'is_string($name):45',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanOrConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Schema::connection($source)->hasColumn($name, \'tenant_id\'):48',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => 'str_starts_with($name, \'partner_\'):50',
        3 => NULL,
      ),
      5 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Schema::connection($connection)->hasTable($name):53',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->option(\'force\'):36',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$this->confirm("Delete ALL shared rows for tenant {$key}?"):36',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/MembershipInactiveException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Exceptions\\MembershipInactiveException',
        1 => 
        array (
          0 => 'm' . "\0" . 'symfony\\component\\httpkernel\\exception\\httpexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        1 => '__construct',
        2 => 15,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantContextMissingException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantHeaderMissingException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Exceptions\\TenantHeaderMissingException',
        1 => 
        array (
          0 => 'm' . "\0" . 'symfony\\component\\httpkernel\\exception\\badrequesthttpexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException',
        1 => '__construct',
        2 => 15,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantNotInTokenException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Exceptions\\TenantNotInTokenException',
        1 => 
        array (
          0 => 'm' . "\0" . 'symfony\\component\\httpkernel\\exception\\httpexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        1 => '__construct',
        2 => 15,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantSuspendedException.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Exceptions\\TenantSuspendedException',
        1 => 
        array (
          0 => 'm' . "\0" . 'symfony\\component\\httpkernel\\exception\\httpexception' . "\0" . '__construct',
        ),
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        1 => '__construct',
        2 => 15,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\ConstructorWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Http\\Middleware\\ResolveTenantContext',
        1 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Models/Tenant.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Models\\Tenant',
        1 => 'newFactory',
        2 => 'App\\Platform\\Tenancy\\Models\\Tenant',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Models\\Tenant',
        1 => 'memberships',
        2 => 'App\\Platform\\Tenancy\\Models\\Tenant',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'hasmany',
        ),
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/Models/TenantMembership.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        1 => 'newFactory',
        2 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\factories\\factory' . "\0" . 'new',
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        1 => 'tenant',
        2 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'belongsto',
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        1 => 'user',
        2 => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
        3 => 
        array (
          0 => 'm' . "\0" . 'illuminate\\database\\eloquent\\model' . "\0" . 'belongsto',
        ),
      ),
    ),
    'PHPStan\\Rules\\Traits\\TraitUseCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
      1 => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/TenantContext.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'isResolved',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'tenantId',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'membershipId',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
      3 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'orgId',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
      4 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'connectionName',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
      5 => 
      array (
        0 => 'App\\Platform\\Tenancy\\TenantContext',
        1 => 'actor',
        2 => 'App\\Platform\\Tenancy\\TenantContext',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/TenantPromotion.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Schema::connection($connection)->hasColumn($name, \'tenant_id\'):29',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '\\Illuminate\\Support\\Facades\\Schema::connection($targetConnection)->hasTable($table):86',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureFuncCallCollector' => 
    array (
      0 => 
      array (
        0 => 'sort',
        1 => 33,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureMethodCallCollector' => 
    array (
      0 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 112,
      ),
      1 => 
      array (
        0 => 
        array (
          0 => 'Illuminate\\Database\\Eloquent\\Model',
        ),
        1 => 'save',
        2 => 123,
      ),
    ),
  ),
  '../../../app/Platform/Tenancy/TenantScope.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '$context->isResolved():28',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanAndConstantConditionRule',
        1 => NULL,
        2 => '$model->includesPlatformDefaults():37',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Providers/EventServiceProvider.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Providers\\EventServiceProvider',
        1 => 'shouldDiscoverEvents',
        2 => 'App\\Providers\\EventServiceProvider',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../app/Providers/PlatformServiceProvider.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '$this->app->runningInConsole():71',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => 'is_string($tenantId):110',
        3 => NULL,
      ),
    ),
  ),
  '../../../app/Providers/RouteServiceProvider.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Support\\Facades\\RateLimiter',
        1 => 'for',
        2 => 27,
      ),
    ),
  ),
  '../../../app/Support/Money.php' => 
  array (
    'PHPStan\\Rules\\DeadCode\\MethodWithoutImpurePointsCollector' => 
    array (
      0 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'roundHalfUp',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      1 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'minorUnits',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      2 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'isZero',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      3 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'isNegative',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      4 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'isPositive',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      5 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'compareTo',
        2 => 'App\\Support\\Money',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\support\\money' . "\0" . 'assertsamecurrency',
        ),
      ),
      6 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'equals',
        2 => 'App\\Support\\Money',
        3 => 
        array (
          0 => 'm' . "\0" . 'app\\support\\money' . "\0" . 'compareto',
        ),
      ),
      7 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'toDecimal',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
      8 => 
      array (
        0 => 'App\\Support\\Money',
        1 => 'toArray',
        2 => 'App\\Support\\Money',
        3 => 
        array (
        ),
      ),
    ),
  ),
  '../../../config/database.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\defined(\'Pdo\\Mysql::ATTR_SSL_CA\'):10',
        3 => NULL,
      ),
      1 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\extension_loaded(\'pdo_mysql\'):75',
        3 => NULL,
      ),
      2 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\extension_loaded(\'pdo_mysql\'):96',
        3 => NULL,
      ),
      3 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\extension_loaded(\'pdo_mysql\'):116',
        3 => NULL,
      ),
      4 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\TernaryOperatorConstantConditionRule',
        1 => NULL,
        2 => '\\extension_loaded(\'pdo_mysql\'):136',
        3 => NULL,
      ),
    ),
  ),
  '../../../routes/api.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\is_file($routes):25',
        3 => NULL,
      ),
    ),
  ),
  '../../../routes/platform/api.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\BooleanNotConstantConditionRule',
        1 => NULL,
        2 => '\\is_file($path):78',
        3 => NULL,
      ),
    ),
  ),
  '../../../routes/web.php' => 
  array (
    'PHPStan\\Rules\\Comparison\\FunctionCallConstantConditionCollector' => 
    array (
      0 => 
      array (
        0 => 'PHPStan\\Rules\\Comparison\\IfConstantConditionRule',
        1 => NULL,
        2 => '\\is_file($routes):31',
        3 => NULL,
      ),
    ),
    'PHPStan\\Rules\\DeadCode\\PossiblyPureStaticCallCollector' => 
    array (
      0 => 
      array (
        0 => 'Illuminate\\Support\\Facades\\Route',
        1 => 'group',
        2 => 40,
      ),
    ),
  ),
); },
	'dependencies' => array (
  '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php' => 
  array (
    'fileHash' => 'e8a89631705d7fe1ee11f59ec64e78cd0e03ea973ac675505eac9974b034b7bb',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php' => 
  array (
    'fileHash' => '93f2fbac5be61ba6f280f0f6dee950717fef65943e773c7b33708d4a1fdab7bb',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php' => 
  array (
    'fileHash' => '3304c7b30ccd2451773859ec1a2e0be688448636febe5ee4397cff9c4a1ab0c6',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php' => 
  array (
    'fileHash' => 'cf6f2c9a80a7eb5cdf09eacafbca0f263c6403d16dd6ab54089abc3e386e3a38',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/NewPasswordController.php' => 
  array (
    'fileHash' => '5be727cac72d74ffc089cc19ad58ddcd6621c41b8e6a27a530c5477fe82193bf',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php' => 
  array (
    'fileHash' => 'a4dcbcc8846989ce47488c480af1ce2a7edb5c2c2b56d17b705e41581c6e7558',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/RegisteredUserController.php' => 
  array (
    'fileHash' => '94b414a8563cbcb41c512a7bff6ac8c2701d00024eb042936e22800ba1f6e227',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Auth/VerifyEmailController.php' => 
  array (
    'fileHash' => '6280eb8e3aa7983725ab76a94045ca1c9eceb3bcb751298a85d190324346140b',
    'dependentFiles' => 
    array (
      0 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/Controller.php' => 
  array (
    'fileHash' => '25d1c1ef8e6cc8a376553faacfba2b07d9dfaee9bdbb84f14f77517580e9deb1',
    'dependentFiles' => 
    array (
      0 => '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php',
      1 => '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php',
      2 => '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php',
      3 => '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php',
      4 => '../../../app/Http/Controllers/Auth/NewPasswordController.php',
      5 => '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php',
      6 => '../../../app/Http/Controllers/Auth/RegisteredUserController.php',
      7 => '../../../app/Http/Controllers/Auth/VerifyEmailController.php',
      8 => '../../../routes/auth.php',
    ),
  ),
  '../../../app/Http/Controllers/RoutingController.php' => 
  array (
    'fileHash' => '9fdf87a5123854e74325fb322c034638860f3cd0e4b611e67f8df52672e063a6',
    'dependentFiles' => 
    array (
      0 => '../../../routes/web.php',
    ),
  ),
  '../../../app/Http/Requests/Auth/LoginRequest.php' => 
  array (
    'fileHash' => '2288f0c61b22ea2ae2547b5dbb3b0d66724752ecca016ef9b62681dd1582914f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php',
    ),
  ),
  '../../../app/Models/User.php' => 
  array (
    'fileHash' => '8c522c4c1eb07aef3c84e5a41ff9c436488655b1a17956350c9387649d1b9b11',
    'dependentFiles' => 
    array (
      0 => '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php',
      1 => '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php',
      2 => '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php',
      3 => '../../../app/Http/Controllers/Auth/RegisteredUserController.php',
      4 => '../../../app/Http/Controllers/Auth/VerifyEmailController.php',
      5 => '../../../app/Http/Controllers/RoutingController.php',
      6 => '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php',
      7 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
      8 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      9 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      10 => '../../../app/Platform/Audit/AuditWriter.php',
      11 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      12 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      13 => '../../../app/Platform/Identity/Http/Controllers/IdentitySelfServiceController.php',
      14 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      15 => '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php',
      16 => '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php',
      17 => '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      18 => '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php',
      19 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
      20 => '../../../app/Platform/Tenancy/Models/TenantMembership.php',
      21 => '../../../app/Providers/RouteServiceProvider.php',
      22 => '../../../config/auth.php',
    ),
  ),
  '../../../app/Modules/Catalog/Database/Migrations/2026_10_01_000001_create_catalog_items_table.php' => 
  array (
    'fileHash' => '5b15b4c5d387e83a8375ffa92c28cd022c50699e595052644de7f9663ff98f26',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Catalog/Database/Migrations/2026_10_01_000002_create_catalog_prices_table.php' => 
  array (
    'fileHash' => '9a26a82fd907dbabd50a51bd7de4a5ba0ff409e2e822d41cb38e694cdcf247ea',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php' => 
  array (
    'fileHash' => '6ba8f3234cb8d6e91d8ebbd43e01a3fb35ad240f5e2cc4909529c360d749b69f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/PriceController.php' => 
  array (
    'fileHash' => '97fb44d6c4b7af0cbd33860548ee95b4754b92886f652ad628c747ab775cfd76',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Catalog/Models/CatalogItem.php' => 
  array (
    'fileHash' => 'aa3a076e812a52e8e31298b4c99ca43ad8a2d04375fb70f7e275d3eefa8023b2',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php',
      1 => '../../../app/Modules/Catalog/Http/Controllers/PriceController.php',
      2 => '../../../app/Modules/Catalog/QuoteCalculator.php',
    ),
  ),
  '../../../app/Modules/Catalog/Models/CatalogPrice.php' => 
  array (
    'fileHash' => '1c86f989b3dc0a4d4b01c0a4432b260c2b2d01709f5431889d08eed833bf7277',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/PriceController.php',
      1 => '../../../app/Modules/Catalog/QuoteCalculator.php',
    ),
  ),
  '../../../app/Modules/Catalog/ModuleServiceProvider.php' => 
  array (
    'fileHash' => 'cf12ddcdd172007e72c320abcdf659001a4ea050d7f25e39e216777063ec9c92',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Catalog/QuoteCalculator.php' => 
  array (
    'fileHash' => '15704cefacfddc3fb23c9513abb89a042a8bfa8d9da0bd569786e908c1602411',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/PriceController.php',
    ),
  ),
  '../../../app/Modules/Catalog/QuoteFailedException.php' => 
  array (
    'fileHash' => 'a825c532167f1031b685935eda5c064cc24d8fb05636872b5ce3fa11d82eedd4',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/PriceController.php',
      1 => '../../../app/Modules/Catalog/QuoteCalculator.php',
    ),
  ),
  '../../../app/Modules/Catalog/Routes/api.php' => 
  array (
    'fileHash' => '4a67728cdae6892a8b6b2e13b160628c163161211510f31544e90327aa7a220d',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000001_create_partner_applications_table.php' => 
  array (
    'fileHash' => '14e2e60344c7b90f9f2b04e6b1cf7611c6c282a76dbd6925d8fe95518257c270',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000002_onboarding_evidence_documents.php' => 
  array (
    'fileHash' => '05f156baf5dc96b27b74cc50825a226546d08d1415744b3d3e2be203f3e2594b',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000003_onboarding_otp_challenges.php' => 
  array (
    'fileHash' => 'c4268c93bdd05df17947c11437889082609db542001ed139a7d1f275d70ef13d',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000004_onboarding_review_records.php' => 
  array (
    'fileHash' => '1386bd890fa9769e7748011f1ed143381a6159b092226b5bd32653bc3f450e33',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php' => 
  array (
    'fileHash' => 'c8870b300dfd92fdb74920c7ed56a595cf5ce52319c63945cab67fa6b4f3df17',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php' => 
  array (
    'fileHash' => 'b899ecc7e2e98bb264574c3ea5e9cbc9517483dbe54b32766a81114002b35df1',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php' => 
  array (
    'fileHash' => 'fe6b89d5217515c9d0138de9db17f4e26a48199ed0ec463ba2a164a512f8633e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php' => 
  array (
    'fileHash' => 'df2fc08535baf66e9e6be9f823dab6375cef800388229a69568b71865d878ad6',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Routes/api.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Models/ApplicationDocument.php' => 
  array (
    'fileHash' => 'f7b243e881fef8f113469c3495f5598d4a308289c2e906853ab393f00813b6a4',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Models/OtpChallenge.php' => 
  array (
    'fileHash' => 'e01c265c6e2259467d2e31946b311b39bfe3fdc672d92c84996839391aa4eda5',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      1 => '../../../app/Modules/Onboarding/OtpService.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Models/PartnerApplication.php' => 
  array (
    'fileHash' => 'c8be0291c4691fbe9279f42195baf207c7d739b4a4fbb5c9548215d0bbbf1eba',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php',
      1 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
      2 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      3 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      4 => '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Models/ReviewRecord.php' => 
  array (
    'fileHash' => '1e31acde4f7acf7d733187ad1c49ffb06823695cd10344b688b0293ef4c58c21',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
    ),
  ),
  '../../../app/Modules/Onboarding/ModuleServiceProvider.php' => 
  array (
    'fileHash' => 'f90c2817e60aba960f73032807fffb8549cde1d54b9882558bdaf54a1fc09d4b',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php' => 
  array (
    'fileHash' => 'd2fd603481111787d4f269a14049ff8cc4a45c763813191a4fb392b7b0b5af1c',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
      1 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
    ),
  ),
  '../../../app/Modules/Onboarding/OtpCodeGenerator.php' => 
  array (
    'fileHash' => 'adc5d602177647eb9400fa481e08816ff2f57abe571975bcbe70de530c50616a',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      1 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
      2 => '../../../app/Modules/Onboarding/OtpService.php',
      3 => '../../../app/Modules/Onboarding/RandomOtpCodes.php',
      4 => '../../../app/Modules/Onboarding/SeededOtpCodes.php',
    ),
  ),
  '../../../app/Modules/Onboarding/OtpRateLimitedException.php' => 
  array (
    'fileHash' => '0e3aa862f47abc964c4ea7edbfbb400a34d6d5f5a91c6d229e8583d85f5e8a99',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      1 => '../../../app/Modules/Onboarding/OtpService.php',
    ),
  ),
  '../../../app/Modules/Onboarding/OtpService.php' => 
  array (
    'fileHash' => '904aea982cb47e130ac91a021ff3c07e47e5c840cefa476bd7fb535092032e85',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      1 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
    ),
  ),
  '../../../app/Modules/Onboarding/RandomOtpCodes.php' => 
  array (
    'fileHash' => '2de2c12e114f5e81687c594e64097153fea256010c6921a056f50dd87e566796',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      1 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
    ),
  ),
  '../../../app/Modules/Onboarding/Routes/api.php' => 
  array (
    'fileHash' => '8dd73da42ed5ab8d6395bd0826a950fe85fd55f827637ee011ceb8e234f9690d',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/SeededOtpCodes.php' => 
  array (
    'fileHash' => 'e389c21fdcb3c9c27c096c981b78193891d685a0936e6adbbc4d846963097353',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Modules/Onboarding/StepValidators.php' => 
  array (
    'fileHash' => '14df68249aa62571ff96bd2ec1b397e733e7e596f7bc471d0570c78cfe39a486',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
    ),
  ),
  '../../../app/Modules/Onboarding/TrackCatalog.php' => 
  array (
    'fileHash' => '935101504f10822331bd335eeb312585fc5f9c962a987579fe5c945550fbc378',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
      1 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      2 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
    ),
  ),
  '../../../app/Platform/Api/CodedHttpException.php' => 
  array (
    'fileHash' => 'd1d5aee6051da87159f4d266873c35df99f45abca267ce0e33be9d8238dcc734',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      2 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      3 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Api/Console/ApiComposeCommand.php' => 
  array (
    'fileHash' => '8a2e4bc412ab92f2a9e0ec49c24cbb17cc63542fd8e3d5512ed6e424c0207aff',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Api/CursorPagination.php' => 
  array (
    'fileHash' => '3f6d28096a5e5a25b9622b779d21f56c49ad2f0028721b942aef6f52bf7b0393',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      2 => '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php',
    ),
  ),
  '../../../app/Platform/Api/ErrorEnvelope.php' => 
  array (
    'fileHash' => 'bd9d78123c12287060102e729fd9cde3b216a852bf5463bba9e79291a67116fb',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Api/OpenApiComposer.php' => 
  array (
    'fileHash' => 'cbc5f664f2ba5a023d27c7e25e4499efb6405ea76bf2ab732cf5180adb1f566b',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/Console/ApiComposeCommand.php',
    ),
  ),
  '../../../app/Platform/Api/ResponseMessages.php' => 
  array (
    'fileHash' => '3738be38c9dc9430cba87edf037cb834e6112929bfe659dddc9f7531abbbdcd0',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
    ),
  ),
  '../../../app/Platform/Audit/AuditLog.php' => 
  array (
    'fileHash' => '44dbcff14425d3c929904b784ac92a42563058c9f680e38e1eb9f0476fdc7c0f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      1 => '../../../app/Platform/Audit/AuditWriter.php',
      2 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      3 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Audit/AuditWriter.php' => 
  array (
    'fileHash' => '01f78547c7df56af0247fefbee342ef33e6f22fb6cd7c1681ac2af5452058052',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      1 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      2 => '../../../app/Platform/Authz/PolicyService.php',
      3 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/AuthorizationDecision.php' => 
  array (
    'fileHash' => '1fef6131f99e91013ed36043bd00d918cfe4170b6a605b94b2f865e48f88e714',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      1 => '../../../app/Platform/Authz/DecisionCache.php',
      2 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      3 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      4 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/AuthorizationRequest.php' => 
  array (
    'fileHash' => 'c0f9488a3a4df60afe857f968bc1f2a68d9828f1ce4c6a9497e5e0b227ec2817',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      2 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      3 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/AuthzCacheInvalidator.php' => 
  array (
    'fileHash' => '84e5dc73dd392d42c6726db996e9e5fc8687401692152e4a1ac06463b974ecc8',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/CapabilityAuthorizer.php' => 
  array (
    'fileHash' => '7fa08f102abb7d912fb2a3755a2dcc74289276defcd2210e2a20e85f54164a5b',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/CapabilityParser.php' => 
  array (
    'fileHash' => 'd3325bfc1615b8fe59e266b59eafb112c9b04f09edec71c32c607db629627c94',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/Contracts/Capability.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      3 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/ConditionEvaluator.php' => 
  array (
    'fileHash' => '6244c35a85285dd3b3f7bd61cb2042cc9eca84c564c5391fd80fe24ee39f4822',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      1 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Console/AuthzExplainCommand.php' => 
  array (
    'fileHash' => '79c42253258d653e2bba3d3da6efc93bda3100106481c660a28c0c3e064f40a9',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php' => 
  array (
    'fileHash' => 'fad807ee41706756a62fe6c8d18d9a15f9bdda166fc3fa82f996911453a25684',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/Capability.php' => 
  array (
    'fileHash' => '9fefbbec1814a2e939acecd81c2a7fbbff2acd5275ffb167d3b284d0c361de7f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/CapabilityParser.php',
      1 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/CapabilitySelector.php' => 
  array (
    'fileHash' => 'd426f4dec5ed0ab6bf5deadbcb2474d9b01e3444ebdc11d68ec9284a0a08b23e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      2 => '../../../app/Platform/Authz/PolicyValidator.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/Condition.php' => 
  array (
    'fileHash' => '476422e665d95cfe280fad0e069740b9617e778b41091e7e397d7a6d09eee937',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/ConditionEvaluator.php',
      1 => '../../../app/Platform/Authz/PolicyValidator.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/DecidingStage.php' => 
  array (
    'fileHash' => '0252859d7f166580c451ff599a8291a343ea595db579d7613424e9528d90dcbb',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/AuthorizationDecision.php',
      1 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/Effect.php' => 
  array (
    'fileHash' => '19d891891b073359fa00b09fd2a876c3256e4879b4f4c4fe3fed7741b4b9a4d5',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      1 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      2 => '../../../app/Platform/Authz/PolicyValidator.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/PolicyStatus.php' => 
  array (
    'fileHash' => 'f137cf707164c4112047b880b51bcba23f0e45cd8f8fa5156244e380fdc1ddee',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      1 => '../../../app/Platform/Authz/Models/Policy.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      3 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Authz/Contracts/ScopeBinding.php' => 
  array (
    'fileHash' => '205121e3ef7303b79faee7b3e9bc8da5abebabf3c1882e31a0f3c06da1ceffaa',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyValidator.php',
    ),
  ),
  '../../../app/Platform/Authz/DecisionCache.php' => 
  array (
    'fileHash' => '26754a7cfd7a5f8137640af7de26d007720d9c5457664b33ede5a4c010a2e472',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/AuthzCacheInvalidator.php',
      1 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      2 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyCreated.php' => 
  array (
    'fileHash' => '536640f72f05f1e074bf6c6a3b0f137f57577c3aa7bc9f1facffc9d79db8c8ce',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyStatusChanged.php' => 
  array (
    'fileHash' => 'e23501956af452c663532c9e6b41b1bdd620a712bfc444cd3b58c0572ecad919',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Authz/Events/PolicyUpdated.php' => 
  array (
    'fileHash' => 'f0d7ff5957a597b4aebd8c133192aa560955ac3db18fabaad762334372d1f2fb',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyService.php',
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/AuthorizationDeniedException.php' => 
  array (
    'fileHash' => '572aaf220b88f9f9ffbe653957f29320328bcaaf079e48ea3dcc30c4e7441b3c',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Authz/CapabilityAuthorizer.php',
      2 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      3 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/CapabilityParseException.php' => 
  array (
    'fileHash' => '99c153156eb1d1a03a73a39deb52d7910f304e039613cd9f36d40524670ae422',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/CapabilityParser.php',
      1 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
    ),
  ),
  '../../../app/Platform/Authz/Exceptions/InvalidPolicyPayloadException.php' => 
  array (
    'fileHash' => '0c9a95bf6ce56aa066fe0f24b653010b68afc8dd394c177ad94a677d6b219d16',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      2 => '../../../app/Platform/Authz/Contracts/CapabilitySelector.php',
      3 => '../../../app/Platform/Authz/Contracts/Condition.php',
      4 => '../../../app/Platform/Authz/Contracts/ScopeBinding.php',
      5 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      6 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      7 => '../../../app/Platform/Authz/PolicyService.php',
      8 => '../../../app/Platform/Authz/PolicyValidator.php',
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php' => 
  array (
    'fileHash' => '294d65d33c43b432925de3d4beb459ee54240c32c12b10cd74b80a6d1c2cd620',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/PolicyController.php' => 
  array (
    'fileHash' => '6fc3feaf142f8010e1fde896ea8bcc35737a817c6e2b5f6576341c0355ab4c8d',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Authz/Http/Controllers/RoleController.php' => 
  array (
    'fileHash' => 'a380eb363e98e35787013538a8d110cf42f1e461b5d4e254484d73e66144f71f',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php' => 
  array (
    'fileHash' => 'fa466423e3abe89b9c12181836e9508895d6167f8204ad5fbaeae3b1321993ec',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Authz/Models/CapabilityMetadata.php' => 
  array (
    'fileHash' => '416e7674ccff7aa62e16155bb0d0fb927e8709706b6faee01a43841703cb4010',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      2 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php' => 
  array (
    'fileHash' => '339eba968e4b35c3d674aa65dceefd103a1d12ccbfb7de86511f1b7946bba557',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      1 => '../../../app/Platform/Authz/Models/Role.php',
      2 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
      3 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      4 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Models/Policy.php' => 
  array (
    'fileHash' => 'ea4411e0d803f3dbee2086bde470a1f32fade47e719e595ab36b5e2a5593ca73',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/Events/PolicyCreated.php',
      2 => '../../../app/Platform/Authz/Events/PolicyStatusChanged.php',
      3 => '../../../app/Platform/Authz/Events/PolicyUpdated.php',
      4 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      5 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      6 => '../../../app/Platform/Authz/PolicyService.php',
      7 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Models/Role.php' => 
  array (
    'fileHash' => '988034bbf2bba3fa6de963659f06cfecf6e2bb079dd05b6c73432890e031eeba',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/CapabilityAuthorizer.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      2 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      3 => '../../../app/Platform/Authz/Models/RolePermission.php',
      4 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      5 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      6 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Models/RolePermission.php' => 
  array (
    'fileHash' => '29d9960221c7686d47a27a58296b8a9b475203946c8d32e1d27501b2e77c5038',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/CapabilityAuthorizer.php',
      1 => '../../../app/Platform/Authz/Models/Role.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      3 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/Models/RoleScopeBinding.php' => 
  array (
    'fileHash' => 'c8d5c3c351dab453a2abd78f8dd08331a36c7953a2f9912557cd0e8f39004ba2',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
    ),
  ),
  '../../../app/Platform/Authz/PolicyEvaluationEngine.php' => 
  array (
    'fileHash' => 'b99b96a5a9e0c5392c890f898c02ba6427ffbad1c315e3e41d2f2f5b73c59c78',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      2 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      3 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/PolicyService.php' => 
  array (
    'fileHash' => '9b6d7479036876086d648a580f82ea2fc7002d9059ad38416572199759aec04e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
    ),
  ),
  '../../../app/Platform/Authz/PolicyValidator.php' => 
  array (
    'fileHash' => 'e43342ddcb883b8b677bf81cd2c4a1aacee6ea942028f15c65d76ce3be7a1d06',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/PolicyService.php',
      1 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Authz/PrincipalResolver.php' => 
  array (
    'fileHash' => 'ba76ecb957346379ee44054888cd4dd4ec7d8239eae7f2514862f76e74e0ccff',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      2 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Console/CleanupExpiredCommand.php' => 
  array (
    'fileHash' => '97916ec4f7466c3a382e6a45afbfceecf80173b2e30ebe52bfbe8b408916ce6c',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Console/DoctorCommand.php' => 
  array (
    'fileHash' => '4a052ac6713007ee5808bfa5d23470670bfef15cd262de9b55efaad46ac7a28e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Http/Middleware/AttachRequestId.php' => 
  array (
    'fileHash' => 'ad400172f3930bd64853931ce9dbc3895c64c5f8d1ce849c4dbe18a1f1f2ef4a',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Http/Middleware/LimitJsonBody.php' => 
  array (
    'fileHash' => '85dbf6d9d42720c91d17712a112104a99533a510c8443a08a696bcd3f39b29fe',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Identity/ActorTokenVerifier.php' => 
  array (
    'fileHash' => 'a6bf49c86e7ac491f931f3d1fdd2c03aa62bd6885459d67560f784f76a972b78',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php',
    ),
  ),
  '../../../app/Platform/Identity/Contracts/TrackSeeder.php' => 
  array (
    'fileHash' => 'ae8d478ccd706f6e139060933b99d82c98553ff44f461df75b619b545995a567',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
      1 => '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php',
      2 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/IdentitySelfServiceController.php' => 
  array (
    'fileHash' => '2a318abaff8c9cd957b6fa99999b6d654c058bb7f8a03330e6e3248aa699d4e7',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/InviteController.php' => 
  array (
    'fileHash' => '6ad4a00c99216b36372dd09c61da0e846b8fe0b9b25c6e5a393d3837440fce46',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php' => 
  array (
    'fileHash' => '618869903802307f8d8ed3cd15470078a17cefeccd72ba3c66904d57d3861020',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php' => 
  array (
    'fileHash' => '6050b9e257e3a3f572c7ddbe374c3a72faa11847c44a5e8d4cd485b6495d1122',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Identity/Models/Invite.php' => 
  array (
    'fileHash' => '0de2bf389d549f256488f549a868abe169458ce7d774131029d76438d4f24719',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Console/CleanupExpiredCommand.php',
      1 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
    ),
  ),
  '../../../app/Platform/Modules/Console/ModuleListCommand.php' => 
  array (
    'fileHash' => '9b7163751bc90ec29878e6e11afad20073737ef86efc24870d18e7f7de39e16c',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Modules/Facades/ModuleRegistry.php' => 
  array (
    'fileHash' => '6661c08e3c12695faee408dab13a7b689d1a2f587ea18a844f1a96ebee4c8523',
    'dependentFiles' => 
    array (
      0 => '../../../routes/api.php',
      1 => '../../../routes/web.php',
    ),
  ),
  '../../../app/Platform/Modules/ManifestValidator.php' => 
  array (
    'fileHash' => '0042a3259ef62ce841f6f9f9b6d9bc2e8ef7a1dc31c1151e9456a2dfbb010b58',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Modules/ModuleRegistry.php',
      2 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Modules/ModuleManifest.php' => 
  array (
    'fileHash' => '7fae9a30a0e00b41a85e402328192f2ec94b1152fadab3e1ce14ee80809d0377',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Modules/Console/ModuleListCommand.php',
      2 => '../../../app/Platform/Modules/Facades/ModuleRegistry.php',
      3 => '../../../app/Platform/Modules/ModuleRegistry.php',
      4 => '../../../routes/api.php',
      5 => '../../../routes/web.php',
    ),
  ),
  '../../../app/Platform/Modules/ModuleRegistry.php' => 
  array (
    'fileHash' => '54387c8fe845b3e8c2e23ff0a750b8b1bce59e3b597ed52e47fdb037f8edaf2f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/Console/ApiComposeCommand.php',
      1 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      3 => '../../../app/Platform/Modules/Console/ModuleListCommand.php',
      4 => '../../../app/Platform/Modules/Facades/ModuleRegistry.php',
      5 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Modules/ModuleServiceProvider.php' => 
  array (
    'fileHash' => '82f9526e7933c529d54235eb29bf2d41eca4f87d6a8d4398ebfc8de22cd0df81',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/ModuleServiceProvider.php',
      1 => '../../../app/Modules/Onboarding/ModuleServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Observability/Health/ReadinessCheck.php' => 
  array (
    'fileHash' => '746de56983e4b1b3abdd3c9e6c673d90627dd997c2707f42d59b5e6c1ec70914',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/web.php',
    ),
  ),
  '../../../app/Platform/Tenancy/BelongsToTenant.php' => 
  array (
    'fileHash' => '3684e2b1e65a7815ea69cd54525b3210fdc3d7d8fa33c97b1ef27fcfc03cac09',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php',
      1 => '../../../app/Modules/Catalog/Http/Controllers/PriceController.php',
      2 => '../../../app/Modules/Catalog/Models/CatalogItem.php',
      3 => '../../../app/Modules/Catalog/Models/CatalogPrice.php',
      4 => '../../../app/Modules/Catalog/QuoteCalculator.php',
      5 => '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php',
      6 => '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php',
      7 => '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php',
      8 => '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php',
      9 => '../../../app/Modules/Onboarding/Models/ApplicationDocument.php',
      10 => '../../../app/Modules/Onboarding/Models/OtpChallenge.php',
      11 => '../../../app/Modules/Onboarding/Models/PartnerApplication.php',
      12 => '../../../app/Modules/Onboarding/Models/ReviewRecord.php',
      13 => '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php',
      14 => '../../../app/Modules/Onboarding/OtpService.php',
      15 => '../../../app/Platform/Audit/AuditLog.php',
      16 => '../../../app/Platform/Audit/AuditWriter.php',
      17 => '../../../app/Platform/Authz/CapabilityAuthorizer.php',
      18 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      19 => '../../../app/Platform/Authz/Events/PolicyCreated.php',
      20 => '../../../app/Platform/Authz/Events/PolicyStatusChanged.php',
      21 => '../../../app/Platform/Authz/Events/PolicyUpdated.php',
      22 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      23 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      24 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      25 => '../../../app/Platform/Authz/Models/CapabilityMetadata.php',
      26 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      27 => '../../../app/Platform/Authz/Models/Policy.php',
      28 => '../../../app/Platform/Authz/Models/Role.php',
      29 => '../../../app/Platform/Authz/Models/RolePermission.php',
      30 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
      31 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      32 => '../../../app/Platform/Authz/PolicyService.php',
      33 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      34 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
    'usedTraitDependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Models/CatalogItem.php',
      1 => '../../../app/Modules/Catalog/Models/CatalogPrice.php',
      2 => '../../../app/Modules/Onboarding/Models/ApplicationDocument.php',
      3 => '../../../app/Modules/Onboarding/Models/OtpChallenge.php',
      4 => '../../../app/Modules/Onboarding/Models/PartnerApplication.php',
      5 => '../../../app/Modules/Onboarding/Models/ReviewRecord.php',
      6 => '../../../app/Platform/Audit/AuditLog.php',
      7 => '../../../app/Platform/Authz/Models/CapabilityMetadata.php',
      8 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      9 => '../../../app/Platform/Authz/Models/Policy.php',
      10 => '../../../app/Platform/Authz/Models/Role.php',
      11 => '../../../app/Platform/Authz/Models/RolePermission.php',
      12 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
    ),
  ),
  '../../../app/Platform/Tenancy/ConnectionResolver.php' => 
  array (
    'fileHash' => '1c3509d204f8904ac6c53c92a646723d821804f00eb63e21b54d26451f82a3c9',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      1 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      2 => '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      3 => '../../../app/Platform/Tenancy/Console/TenantProvisionSchemaCommand.php',
      4 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
      5 => '../../../app/Platform/Tenancy/TenantPromotion.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php' => 
  array (
    'fileHash' => '7d3e1c209cdc21d980ab1e0ffa5d7bc2562975863058e7440e25ad1cd737bf5e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantBackfillV2Command.php' => 
  array (
    'fileHash' => '909a6bbbac9598c75c7652cb438d3e06745374afaf28cfd1f014204b2647084d',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php' => 
  array (
    'fileHash' => '5e8bace7cf2b1cd2f7d585835e2ffd130cb5f88c93ae2e58d05fd0bda3ee5a65',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php' => 
  array (
    'fileHash' => 'dd03092fb19e21adb5f51074630a44ad704fe32c86e4056a640c633bfa98f106',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantProvisionSchemaCommand.php' => 
  array (
    'fileHash' => '2d0c0e5a72416aa6a2d68b68cee8518f6fe3d8bb100bf40d695cb43aeaf4df01',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php' => 
  array (
    'fileHash' => '15980ab285c7cb9f5bfa4959cd4605f5650b44dc9ac0d57d7e6ecb514c3aa66e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/MembershipInactiveException.php' => 
  array (
    'fileHash' => '20612a9be628ef285bd0779ab69828606190844c54dad3299e41269d98e08d76',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantContextMissingException.php' => 
  array (
    'fileHash' => 'd0b3aca53ad8e13230e1fe47cf45b0d915cb13ab6811c22a039c1d4ed8de891d',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Models/CatalogItem.php',
      1 => '../../../app/Modules/Catalog/Models/CatalogPrice.php',
      2 => '../../../app/Modules/Onboarding/Models/ApplicationDocument.php',
      3 => '../../../app/Modules/Onboarding/Models/OtpChallenge.php',
      4 => '../../../app/Modules/Onboarding/Models/PartnerApplication.php',
      5 => '../../../app/Modules/Onboarding/Models/ReviewRecord.php',
      6 => '../../../app/Platform/Audit/AuditLog.php',
      7 => '../../../app/Platform/Audit/AuditWriter.php',
      8 => '../../../app/Platform/Authz/Models/CapabilityMetadata.php',
      9 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      10 => '../../../app/Platform/Authz/Models/Policy.php',
      11 => '../../../app/Platform/Authz/Models/Role.php',
      12 => '../../../app/Platform/Authz/Models/RolePermission.php',
      13 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
      14 => '../../../app/Platform/Authz/PolicyService.php',
      15 => '../../../app/Platform/Tenancy/ConnectionResolver.php',
      16 => '../../../app/Platform/Tenancy/TenantContext.php',
      17 => '../../../app/Platform/Tenancy/TenantPromotion.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantHeaderMissingException.php' => 
  array (
    'fileHash' => 'be1bae7f5d47fd229fe776d027cd3bd32c9cb82c0c0d0ff750f9041396e3b192',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantNotInTokenException.php' => 
  array (
    'fileHash' => '61fb9953af9d6d7df0f66c892bb5abbf5c793deff21e551a17acf6fa93ef1e9f',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantSuspendedException.php' => 
  array (
    'fileHash' => 'ecbbd90bc75c1eec3b599691271159776f5124a6680c8409ec65d3f1f550766e',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Api/ErrorEnvelope.php',
      1 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php' => 
  array (
    'fileHash' => 'cb521873df1e62ae7cff647f928cda629d6ab355a6ce344ed90cbbe9eb86b686',
    'dependentFiles' => 
    array (
      0 => '../../../routes/platform/api.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php' => 
  array (
    'fileHash' => 'fdefa22e1aa7d0c878a50ccab9e8b321507f3def3d88b8666f4c101af538bd01',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Platform/Tenancy/IncludesPlatformDefaults.php' => 
  array (
    'fileHash' => '349c4e569bc4885a39c28a24bb72927f6886b70d2d9e23ac3c6cfdb20663eb1b',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/Events/PolicyCreated.php',
      2 => '../../../app/Platform/Authz/Events/PolicyStatusChanged.php',
      3 => '../../../app/Platform/Authz/Events/PolicyUpdated.php',
      4 => '../../../app/Platform/Authz/Http/Controllers/PolicyController.php',
      5 => '../../../app/Platform/Authz/Models/Policy.php',
      6 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      7 => '../../../app/Platform/Authz/PolicyService.php',
      8 => '../../../app/Platform/Tenancy/TenantScope.php',
      9 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Models/Tenant.php' => 
  array (
    'fileHash' => '765f4e8057b14c0b047131dc8dc74f925a7d74f0c02da3b13203a8897903028d',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      1 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      2 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      3 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      4 => '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php',
      5 => '../../../app/Platform/Tenancy/ConnectionResolver.php',
      6 => '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      7 => '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php',
      8 => '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php',
      9 => '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php',
      10 => '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php',
      11 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
      12 => '../../../app/Platform/Tenancy/Models/TenantMembership.php',
      13 => '../../../app/Platform/Tenancy/TenantPromotion.php',
    ),
  ),
  '../../../app/Platform/Tenancy/Models/TenantMembership.php' => 
  array (
    'fileHash' => 'c7d10ca322ff0489ad1ccdd307c657a45eb3efcbdb7ca1bbcf417aaf64866db3',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      1 => '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php',
      2 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      3 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      4 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      5 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      6 => '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php',
      7 => '../../../app/Platform/Tenancy/ConnectionResolver.php',
      8 => '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      9 => '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php',
      10 => '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php',
      11 => '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php',
      12 => '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php',
      13 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
      14 => '../../../app/Platform/Tenancy/Models/Tenant.php',
      15 => '../../../app/Platform/Tenancy/TenantPromotion.php',
    ),
  ),
  '../../../app/Platform/Tenancy/TenantContext.php' => 
  array (
    'fileHash' => '1487be8568cbce75c2cdcc68311d45ce930674f4cec37506cefab5c9514693e8',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Models/CatalogItem.php',
      1 => '../../../app/Modules/Catalog/Models/CatalogPrice.php',
      2 => '../../../app/Modules/Onboarding/Models/ApplicationDocument.php',
      3 => '../../../app/Modules/Onboarding/Models/OtpChallenge.php',
      4 => '../../../app/Modules/Onboarding/Models/PartnerApplication.php',
      5 => '../../../app/Modules/Onboarding/Models/ReviewRecord.php',
      6 => '../../../app/Platform/Audit/AuditLog.php',
      7 => '../../../app/Platform/Audit/AuditWriter.php',
      8 => '../../../app/Platform/Authz/CapabilityAuthorizer.php',
      9 => '../../../app/Platform/Authz/Console/AuthzExplainCommand.php',
      10 => '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php',
      11 => '../../../app/Platform/Authz/Http/Controllers/RoleController.php',
      12 => '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php',
      13 => '../../../app/Platform/Authz/Models/CapabilityMetadata.php',
      14 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      15 => '../../../app/Platform/Authz/Models/Policy.php',
      16 => '../../../app/Platform/Authz/Models/Role.php',
      17 => '../../../app/Platform/Authz/Models/RolePermission.php',
      18 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
      19 => '../../../app/Platform/Authz/PolicyEvaluationEngine.php',
      20 => '../../../app/Platform/Authz/PolicyService.php',
      21 => '../../../app/Platform/Identity/Http/Controllers/InviteController.php',
      22 => '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php',
      23 => '../../../app/Platform/Tenancy/TenantScope.php',
      24 => '../../../app/Providers/PlatformServiceProvider.php',
    ),
  ),
  '../../../app/Platform/Tenancy/TenantPromotion.php' => 
  array (
    'fileHash' => '722fbdeb727af80a07de1923f9844932cceca88734c9582367da7589137b91e7',
    'dependentFiles' => 
    array (
      0 => '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php',
      1 => '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php',
      2 => '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php',
      3 => '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php',
    ),
  ),
  '../../../app/Platform/Tenancy/TenantScope.php' => 
  array (
    'fileHash' => 'e5deeadd5eaa0e6a7be56a89cc240b215dac174472cc71a7033c6eaa2d39b01a',
    'dependentFiles' => 
    array (
      0 => '../../../app/Modules/Catalog/Models/CatalogItem.php',
      1 => '../../../app/Modules/Catalog/Models/CatalogPrice.php',
      2 => '../../../app/Modules/Onboarding/Models/ApplicationDocument.php',
      3 => '../../../app/Modules/Onboarding/Models/OtpChallenge.php',
      4 => '../../../app/Modules/Onboarding/Models/PartnerApplication.php',
      5 => '../../../app/Modules/Onboarding/Models/ReviewRecord.php',
      6 => '../../../app/Platform/Audit/AuditLog.php',
      7 => '../../../app/Platform/Authz/Models/CapabilityMetadata.php',
      8 => '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php',
      9 => '../../../app/Platform/Authz/Models/Policy.php',
      10 => '../../../app/Platform/Authz/Models/Role.php',
      11 => '../../../app/Platform/Authz/Models/RolePermission.php',
      12 => '../../../app/Platform/Authz/Models/RoleScopeBinding.php',
    ),
  ),
  '../../../app/Providers/AppServiceProvider.php' => 
  array (
    'fileHash' => 'caf306ef6a25a547bbb7edd5508a39b51c365083eb6dd58378cf2242018e94c7',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Providers/AuthServiceProvider.php' => 
  array (
    'fileHash' => '5d44e4fac82838d15cbd319fdc152fc29f93945c09c2dbecf92132a18c6ab314',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Providers/BroadcastServiceProvider.php' => 
  array (
    'fileHash' => '1839af2f801cb9fff772430aa25aa7529b1e7937b2441555c70af4fae1541a99',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Providers/EventServiceProvider.php' => 
  array (
    'fileHash' => 'c0f2d7d14531d8d92edbe4f1c5ed50973213cb4a119d79f8d2c1dfaf849bba78',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Providers/PlatformServiceProvider.php' => 
  array (
    'fileHash' => 'db012f378d7532155e7309b856057e02712761b89c4308d13f7c62f45ddb157d',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../app/Providers/RouteServiceProvider.php' => 
  array (
    'fileHash' => '6008e9ca8bea1b373bddf26febdcc5f3667670085f3a32dda9fe56411c2eca50',
    'dependentFiles' => 
    array (
      0 => '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php',
      1 => '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php',
      2 => '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php',
      3 => '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php',
      4 => '../../../app/Http/Controllers/Auth/RegisteredUserController.php',
      5 => '../../../app/Http/Controllers/Auth/VerifyEmailController.php',
    ),
  ),
  '../../../app/Support/Money.php' => 
  array (
    'fileHash' => '09327664f1054fd5ddd7bb2ad82247a561e02a2174ed3705c8ea2d2f7a4bd781',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/app.php' => 
  array (
    'fileHash' => '5a44387ebeedf16d775122372f5709e2fd76e5bf50f3d36708554721001c82f9',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/auth.php' => 
  array (
    'fileHash' => 'd5bd78db15ec31df30248bb56dff53d385d7826d9ab0cfb2846dc6ce06d531c0',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/blade-icons.php' => 
  array (
    'fileHash' => '77bfbb03fad6abbe082083632830bd2426d602ad1b9061309640b543b48ec058',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/cache.php' => 
  array (
    'fileHash' => 'dc3a64749bc19946ba6a054be0b67ae8c450c9401799cb176ac0a0b8d4756664',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/database.php' => 
  array (
    'fileHash' => 'eacf8bb4a3354e01db6a104329a48bda4186aae61c326239b132de3ae919e556',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/filesystems.php' => 
  array (
    'fileHash' => '4a841e2bd7f5144e9c650f5c2700a4302fe9959c0815b64164058f6c3994a4be',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/identity.php' => 
  array (
    'fileHash' => 'a2e3eb66f34633e800736e18d070d5414c8b16a5dd5825fae1013786107fc968',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/logging.php' => 
  array (
    'fileHash' => '6e0885a09b96f9e3e9ccfae9e536da3c33dbd53a7710a518c05ac0f2b6808fb8',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/mail.php' => 
  array (
    'fileHash' => 'fc1fd1fade3ab4d6e0baddf86114711f35beb0880be836ca1c20134e745d72af',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/platform.php' => 
  array (
    'fileHash' => 'f6aae21fdc8dec78334d381008108de66e6bec11ec6d778f9ce502474bb83711',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/queue.php' => 
  array (
    'fileHash' => 'a185a3e86b65db26938204f4f50cf0c0ca99974e9926b9cdcef6b7743feea482',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/services.php' => 
  array (
    'fileHash' => '2226be3fa0e560fc8cd628f3e8f50fcd78639a24375f5e931d0c656eaf1d4ce8',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../config/session.php' => 
  array (
    'fileHash' => 'd96b4c3d215ad3d0e6570c8620d1b4dd603925c2de9036792ca93414c5cc3ab6',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/api.php' => 
  array (
    'fileHash' => 'a6c2bbd55071fe421fc09a6c56f009ad1239dfbe5fd0fe47d410b314593305c0',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/auth.php' => 
  array (
    'fileHash' => '6e9664823c8483afd791f4de147c5b50d2b04c0e9283cbc56bef7fb6090cc738',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/console.php' => 
  array (
    'fileHash' => '7bff1ca9501485339d5fddb7777e2a5f4144787b9cb1af9cf61c269f38d1752d',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/platform/api.php' => 
  array (
    'fileHash' => 'a57161447897c1accfa88e5ce8fa06a0e83cecd74c9f1e8f19deb655731c08d4',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/platform/web.php' => 
  array (
    'fileHash' => '7984ef102438714396711c63fec346ea7ae7f1c007791fa1a680daeb3a05ddb7',
    'dependentFiles' => 
    array (
    ),
  ),
  '../../../routes/web.php' => 
  array (
    'fileHash' => 'f0fe602a067b8d7d13ffe56fc660116a185077ad728db8509fa27f444791112e',
    'dependentFiles' => 
    array (
    ),
  ),
),
	'packageDependencies' => array (
  '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Controller.php' => 
  array (
  ),
  '../../../app/Models/User.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Api/CursorPagination.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Api/ErrorEnvelope.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Authz/AuthorizationRequest.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Contracts/CapabilitySelector.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Http/Middleware/AttachRequestId.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/uid',
  ),
  '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Tenancy/IncludesPlatformDefaults.php' => 
  array (
  ),
  '../../../app/Providers/EventServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Providers/PlatformServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'psr/container',
    2 => 'symfony/console',
  ),
  '../../../config/app.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../config/filesystems.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../config/mail.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Api/CodedHttpException.php' => 
  array (
    0 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Api/OpenApiComposer.php' => 
  array (
    0 => 'symfony/yaml',
  ),
  '../../../app/Platform/Authz/CapabilityAuthorizer.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/CapabilityParser.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Contracts/DecidingStage.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Events/PolicyStatusChanged.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Authz/Exceptions/AuthorizationDeniedException.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Models/Role.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/PolicyService.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-kernel',
    2 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Authz/PrincipalResolver.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Console/DoctorCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/ModuleRegistry.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'psr/container',
  ),
  '../../../app/Platform/Observability/Health/ReadinessCheck.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'psr/simple-cache',
    3 => 'symfony/http-kernel',
    4 => 'psr/container',
    5 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Tenancy/BelongsToTenant.php' => 
  array (
  ),
  '../../../app/Platform/Tenancy/Console/TenantBackfillV2Command.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantSuspendedException.php' => 
  array (
    0 => 'symfony/http-kernel',
  ),
  '../../../config/auth.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Http/Controllers/Auth/RegisteredUserController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Api/Console/ApiComposeCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
    2 => 'symfony/yaml',
  ),
  '../../../app/Platform/Audit/AuditLog.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Contracts/Capability.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/DecisionCache.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'psr/simple-cache',
  ),
  '../../../app/Platform/Authz/Events/PolicyUpdated.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Authz/PolicyEvaluationEngine.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/Console/ModuleListCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/ManifestValidator.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantContextMissingException.php' => 
  array (
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantNotInTokenException.php' => 
  array (
    0 => 'symfony/http-kernel',
  ),
  '../../../config/logging.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'monolog/monolog',
  ),
  '../../../config/services.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../routes/api.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../routes/console.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/console',
  ),
  '../../../routes/platform/api.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/yaml',
  ),
  '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/RoutingController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Requests/Auth/LoginRequest.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Authz/AuthorizationDecision.php' => 
  array (
  ),
  '../../../app/Platform/Authz/AuthzCacheInvalidator.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Contracts/Condition.php' => 
  array (
    0 => 'mockery/mockery',
  ),
  '../../../app/Platform/Authz/Contracts/ScopeBinding.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Events/PolicyCreated.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Authz/Exceptions/CapabilityParseException.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Models/Policy.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Models/RoleScopeBinding.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/ModuleManifest.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Providers/AppServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Providers/AuthServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../config/blade-icons.php' => 
  array (
  ),
  '../../../config/database.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../routes/web.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Api/ResponseMessages.php' => 
  array (
    0 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Authz/ConditionEvaluator.php' => 
  array (
    0 => 'mockery/mockery',
  ),
  '../../../app/Platform/Authz/Console/AuthzExplainCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/Models/CapabilityMetadata.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/Facades/ModuleRegistry.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Modules/ModuleServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Exceptions/MembershipInactiveException.php' => 
  array (
    0 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Tenancy/TenantContext.php' => 
  array (
  ),
  '../../../app/Platform/Tenancy/TenantScope.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Providers/BroadcastServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Support/Money.php' => 
  array (
  ),
  '../../../config/cache.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../config/platform.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../config/session.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../routes/platform/web.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Auth/NewPasswordController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Http/Controllers/Auth/VerifyEmailController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Audit/AuditWriter.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'nesbot/carbon',
    2 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Authz/Contracts/Effect.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Contracts/PolicyStatus.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Exceptions/InvalidPolicyPayloadException.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Http/Controllers/PolicyController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/http-kernel',
    3 => 'nesbot/carbon',
  ),
  '../../../app/Platform/Authz/Models/RolePermission.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Authz/PolicyValidator.php' => 
  array (
    0 => 'mockery/mockery',
  ),
  '../../../app/Platform/Identity/Http/Controllers/IdentitySelfServiceController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantHeaderMissingException.php' => 
  array (
    0 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'symfony/http-kernel',
  ),
  '../../../app/Platform/Tenancy/Models/Tenant.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Models/TenantMembership.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Providers/RouteServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../config/queue.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../routes/auth.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000001_create_partner_applications_table.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Modules/Onboarding/Models/PartnerApplication.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/ModuleServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'psr/container',
  ),
  '../../../app/Modules/Onboarding/Routes/api.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Tenancy/ConnectionResolver.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'psr/simple-cache',
  ),
  '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Tenancy/TenantPromotion.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000002_onboarding_evidence_documents.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'psr/http-message',
    3 => 'league/flysystem',
  ),
  '../../../app/Modules/Onboarding/Models/ApplicationDocument.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/TrackCatalog.php' => 
  array (
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000003_onboarding_otp_challenges.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'nesbot/carbon',
  ),
  '../../../app/Modules/Onboarding/Models/OtpChallenge.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/OtpCodeGenerator.php' => 
  array (
  ),
  '../../../app/Modules/Onboarding/OtpRateLimitedException.php' => 
  array (
  ),
  '../../../app/Modules/Onboarding/OtpService.php' => 
  array (
    0 => 'nesbot/carbon',
    1 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/RandomOtpCodes.php' => 
  array (
  ),
  '../../../app/Modules/Onboarding/SeededOtpCodes.php' => 
  array (
  ),
  '../../../app/Platform/Tenancy/Console/TenantProvisionSchemaCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Database/Migrations/2026_10_01_000004_onboarding_review_records.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'nesbot/carbon',
  ),
  '../../../app/Modules/Onboarding/Models/ReviewRecord.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/StepValidators.php' => 
  array (
  ),
  '../../../app/Platform/Identity/Http/Controllers/InviteController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'nesbot/carbon',
    3 => 'symfony/http-kernel',
    4 => 'psr/container',
  ),
  '../../../app/Platform/Identity/Models/Invite.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Platform/Identity/Contracts/TrackSeeder.php' => 
  array (
  ),
  '../../../app/Platform/Authz/Http/Controllers/RoleController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Identity/ActorTokenVerifier.php' => 
  array (
  ),
  '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../config/identity.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/Database/Migrations/2026_10_01_000001_create_catalog_items_table.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Modules/Catalog/Models/CatalogItem.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/ModuleServiceProvider.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/Routes/api.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Platform/Console/CleanupExpiredCommand.php' => 
  array (
    0 => 'symfony/console',
    1 => 'laravel/framework',
  ),
  '../../../app/Platform/Http/Middleware/LimitJsonBody.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
  ),
  '../../../app/Modules/Catalog/Database/Migrations/2026_10_01_000002_create_catalog_prices_table.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/Http/Controllers/PriceController.php' => 
  array (
    0 => 'laravel/framework',
    1 => 'symfony/http-foundation',
    2 => 'nesbot/carbon',
  ),
  '../../../app/Modules/Catalog/Models/CatalogPrice.php' => 
  array (
    0 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/QuoteCalculator.php' => 
  array (
    0 => 'nesbot/carbon',
    1 => 'laravel/framework',
  ),
  '../../../app/Modules/Catalog/QuoteFailedException.php' => 
  array (
  ),
),
	'exportedNodesCallback' => static function (): array { return array (
  '../../../app/Http/Controllers/Auth/AuthenticatedSessionController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\AuthenticatedSessionController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'create',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display the login view.
     *
     * @return \\Illuminate\\View\\View
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'loginrequest' => 'App\\Http\\Requests\\Auth\\LoginRequest',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Handle an incoming authentication request.
     *
     * @param  \\App\\Http\\Requests\\Auth\\LoginRequest  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'loginrequest' => 'App\\Http\\Requests\\Auth\\LoginRequest',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'App\\Http\\Requests\\Auth\\LoginRequest',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'destroy',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Destroy an authenticated session.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'loginrequest' => 'App\\Http\\Requests\\Auth\\LoginRequest',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/ConfirmablePasswordController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\ConfirmablePasswordController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'show',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Show the confirm password view.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\View\\View
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Confirm the user\'s password.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return mixed
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationNotificationController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\EmailVerificationNotificationController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Send a new email verification notification.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/EmailVerificationPromptController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\EmailVerificationPromptController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__invoke',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display the email verification prompt.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return mixed
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/NewPasswordController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\NewPasswordController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'create',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display the password reset view.
     *
     * @return \\Illuminate\\View\\View
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'passwordreset' => 'Illuminate\\Auth\\Events\\PasswordReset',
              'request' => 'Illuminate\\Http\\Request',
              'hash' => 'Illuminate\\Support\\Facades\\Hash',
              'password' => 'Illuminate\\Support\\Facades\\Password',
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Handle an incoming new password request.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     *
     * @throws \\Illuminate\\Validation\\ValidationException
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'passwordreset' => 'Illuminate\\Auth\\Events\\PasswordReset',
              'request' => 'Illuminate\\Http\\Request',
              'hash' => 'Illuminate\\Support\\Facades\\Hash',
              'password' => 'Illuminate\\Support\\Facades\\Password',
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/PasswordResetLinkController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\PasswordResetLinkController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'create',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display the password reset link request view.
     *
     * @return \\Illuminate\\View\\View
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'request' => 'Illuminate\\Http\\Request',
              'password' => 'Illuminate\\Support\\Facades\\Password',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Handle an incoming password reset link request.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     *
     * @throws \\Illuminate\\Validation\\ValidationException
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'request' => 'Illuminate\\Http\\Request',
              'password' => 'Illuminate\\Support\\Facades\\Password',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/RegisteredUserController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\RegisteredUserController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'create',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display the registration view.
     *
     * @return \\Illuminate\\View\\View
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'user' => 'App\\Models\\User',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'registered' => 'Illuminate\\Auth\\Events\\Registered',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'hash' => 'Illuminate\\Support\\Facades\\Hash',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Handle an incoming registration request.
     *
     * @param  \\Illuminate\\Http\\Request  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     *
     * @throws \\Illuminate\\Validation\\ValidationException
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'user' => 'App\\Models\\User',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'registered' => 'Illuminate\\Auth\\Events\\Registered',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'hash' => 'Illuminate\\Support\\Facades\\Hash',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Auth/VerifyEmailController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Auth\\VerifyEmailController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'App\\Http\\Controllers\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__invoke',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Mark the authenticated user\'s email address as verified.
     *
     * @param  \\Illuminate\\Foundation\\Auth\\EmailVerificationRequest  $request
     * @return \\Illuminate\\Http\\RedirectResponse
     */',
             'namespace' => 'App\\Http\\Controllers\\Auth',
             'uses' => 
            array (
              'controller' => 'App\\Http\\Controllers\\Controller',
              'routeserviceprovider' => 'App\\Providers\\RouteServiceProvider',
              'verified' => 'Illuminate\\Auth\\Events\\Verified',
              'emailverificationrequest' => 'Illuminate\\Foundation\\Auth\\EmailVerificationRequest',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Foundation\\Auth\\EmailVerificationRequest',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/Controller.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\Controller',
       'phpDoc' => NULL,
       'abstract' => true,
       'final' => false,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Controllers/RoutingController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Controllers\\RoutingController',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Routing\\Controller',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
        1 => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display a listing of the resource.
     *
     * @return \\Illuminate\\Http\\Response
     */',
             'namespace' => 'App\\Http\\Controllers',
             'uses' => 
            array (
              'authorizesrequests' => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
              'validatesrequests' => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
              'basecontroller' => 'Illuminate\\Routing\\Controller',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'root',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Display a view based on first route param
     *
     * @return \\Illuminate\\Http\\Response
     */',
             'namespace' => 'App\\Http\\Controllers',
             'uses' => 
            array (
              'authorizesrequests' => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
              'validatesrequests' => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
              'basecontroller' => 'Illuminate\\Routing\\Controller',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'first',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'secondLevel',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * second level route
     */',
             'namespace' => 'App\\Http\\Controllers',
             'uses' => 
            array (
              'authorizesrequests' => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
              'validatesrequests' => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
              'basecontroller' => 'Illuminate\\Routing\\Controller',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'first',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'second',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'thirdLevel',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * third level route
     */',
             'namespace' => 'App\\Http\\Controllers',
             'uses' => 
            array (
              'authorizesrequests' => 'Illuminate\\Foundation\\Auth\\Access\\AuthorizesRequests',
              'validatesrequests' => 'Illuminate\\Foundation\\Validation\\ValidatesRequests',
              'basecontroller' => 'Illuminate\\Routing\\Controller',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'first',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'second',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'third',
               'type' => NULL,
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Http/Requests/Auth/LoginRequest.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Http\\Requests\\Auth\\LoginRequest',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Foundation\\Http\\FormRequest',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'authorize',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Determine if the user is authorized to make this request.
     */',
             'namespace' => 'App\\Http\\Requests\\Auth',
             'uses' => 
            array (
              'lockout' => 'Illuminate\\Auth\\Events\\Lockout',
              'formrequest' => 'Illuminate\\Foundation\\Http\\FormRequest',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'str' => 'Illuminate\\Support\\Str',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'rules',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \\Illuminate\\Contracts\\Validation\\ValidationRule|array<mixed>|string>
     */',
             'namespace' => 'App\\Http\\Requests\\Auth',
             'uses' => 
            array (
              'lockout' => 'Illuminate\\Auth\\Events\\Lockout',
              'formrequest' => 'Illuminate\\Foundation\\Http\\FormRequest',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'str' => 'Illuminate\\Support\\Str',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'authenticate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Attempt to authenticate the request\'s credentials.
     *
     * @return void
     *
     * @throws \\Illuminate\\Validation\\ValidationException
     */',
             'namespace' => 'App\\Http\\Requests\\Auth',
             'uses' => 
            array (
              'lockout' => 'Illuminate\\Auth\\Events\\Lockout',
              'formrequest' => 'Illuminate\\Foundation\\Http\\FormRequest',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'str' => 'Illuminate\\Support\\Str',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'ensureIsNotRateLimited',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Ensure the login request is not rate limited.
     *
     * @return void
     *
     * @throws \\Illuminate\\Validation\\ValidationException
     */',
             'namespace' => 'App\\Http\\Requests\\Auth',
             'uses' => 
            array (
              'lockout' => 'Illuminate\\Auth\\Events\\Lockout',
              'formrequest' => 'Illuminate\\Foundation\\Http\\FormRequest',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'str' => 'Illuminate\\Support\\Str',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'throttleKey',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Get the rate limiting throttle key for the request.
     *
     * @return string
     */',
             'namespace' => 'App\\Http\\Requests\\Auth',
             'uses' => 
            array (
              'lockout' => 'Illuminate\\Auth\\Events\\Lockout',
              'formrequest' => 'Illuminate\\Foundation\\Http\\FormRequest',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'str' => 'Illuminate\\Support\\Str',
              'validationexception' => 'Illuminate\\Validation\\ValidationException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Models/User.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Models\\User',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Foundation\\Auth\\User',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        1 => 'Illuminate\\Notifications\\Notifiable',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */',
             'namespace' => 'App\\Models',
             'uses' => 
            array (
              'userfactory' => 'Database\\Factories\\UserFactory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'authenticatable' => 'Illuminate\\Foundation\\Auth\\User',
              'notifiable' => 'Illuminate\\Notifications\\Notifiable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'hidden',
          ),
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */',
             'namespace' => 'App\\Models',
             'uses' => 
            array (
              'userfactory' => 'Database\\Factories\\UserFactory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'authenticatable' => 'Illuminate\\Foundation\\Auth\\User',
              'notifiable' => 'Illuminate\\Notifications\\Notifiable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */',
             'namespace' => 'App\\Models',
             'uses' => 
            array (
              'userfactory' => 'Database\\Factories\\UserFactory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'authenticatable' => 'Illuminate\\Foundation\\Auth\\User',
              'notifiable' => 'Illuminate\\Notifications\\Notifiable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/CatalogItemController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\Http\\Controllers\\CatalogItemController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Catalog item reads + lifecycle writes. Creates start DRAFT (publish is
 * an explicit verb, never a flag flip); SKU uniqueness is per tenant
 * (TEN-010); money stays minor-unit integers on the wire here (event
 * payloads) per api-platform §3.1 note.
 */',
         'namespace' => 'App\\Modules\\Catalog\\Http\\Controllers',
         'uses' => 
        array (
          'catalogitem' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
          'cursorpagination' => 'App\\Platform\\Api\\CursorPagination',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'show',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'publish',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'update',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'archive',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/Http/Controllers/PriceController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\Http\\Controllers\\PriceController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Catalog pricing: effective-dated minor-unit prices with supersede on
 * write (exactly one open row per sku/market/currency), plus a
 * deterministic quote endpoint (unknown SKUs and currency mismatches fail
 * the whole quote — never partial totals).
 */',
         'namespace' => 'App\\Modules\\Catalog\\Http\\Controllers',
         'uses' => 
        array (
          'catalogitem' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
          'catalogprice' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
          'quotecalculator' => 'App\\Modules\\Catalog\\QuoteCalculator',
          'quotefailedexception' => 'App\\Modules\\Catalog\\QuoteFailedException',
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'quote',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/Models/CatalogItem.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Modules\\Catalog\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
        2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_DRAFT',
               'value' => '\'DRAFT\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_ACTIVE',
               'value' => '\'ACTIVE\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_ARCHIVED',
               'value' => '\'ARCHIVED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return \\Database\\Factories\\CatalogItemFactory
     */',
             'namespace' => 'App\\Modules\\Catalog\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/Models/CatalogPrice.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Effective-dated price row (minor units, never floats).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $sku
 * @property string $market
 * @property string $currency
 * @property int $price_minor
 * @property \\Illuminate\\Support\\Carbon $effective_from
 * @property \\Illuminate\\Support\\Carbon|null $effective_to
 */',
         'namespace' => 'App\\Modules\\Catalog\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/ModuleServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\ModuleServiceProvider',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Catalog module provider: loads migrations; routes mount file-based.
 */',
         'namespace' => 'App\\Modules\\Catalog',
         'uses' => 
        array (
          'basemoduleserviceprovider' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'App\\Platform\\Modules\\ModuleServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'registerModule',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'bootModule',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/QuoteCalculator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\QuoteCalculator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Deterministic quote calculation (Wave 3 pricing, promotions excluded):
 * each line resolves its effective price at the quote instant; unknown
 * SKUs and currency mismatches fail the whole quote (never partial
 * totals). Integer minor-unit arithmetic only — no floats anywhere.
 */',
         'namespace' => 'App\\Modules\\Catalog',
         'uses' => 
        array (
          'catalogitem' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
          'catalogprice' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'quote',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param list<array{sku: string, quantity: int}> $lines
     * @return array{lines: list<array{sku: string, quantity: int, unit_minor: int, line_minor: int}>, total_minor: int, currency: string}
     */',
             'namespace' => 'App\\Modules\\Catalog',
             'uses' => 
            array (
              'catalogitem' => 'App\\Modules\\Catalog\\Models\\CatalogItem',
              'catalogprice' => 'App\\Modules\\Catalog\\Models\\CatalogPrice',
              'carbonimmutable' => 'Carbon\\CarbonImmutable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'currency',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'lines',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'at',
               'type' => 'Carbon\\CarbonImmutable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Catalog/QuoteFailedException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Catalog\\QuoteFailedException',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => true,
       'extends' => 'RuntimeException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ApplicationController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Http\\Controllers\\ApplicationController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Onboarding application intake (stage 1-5 port from the Next.js BFF):
 * version-pinned track start + draft read. Tenant + ownership enforced by
 * middleware + scope; submit/review/decision land next.
 */',
         'namespace' => 'App\\Modules\\Onboarding\\Http\\Controllers',
         'uses' => 
        array (
          'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'start',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'show',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/DraftController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Http\\Controllers\\DraftController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Draft evidence, status and document intake for onboarding applications.
 * Ownership + tenant scope enforced per route; step names validated
 * against the version-pinned track catalog (unknown steps 422, never
 * stored). Uploaded bytes land on the local disk (object-storage port
 * selects the backend later); metadata is the tenant-scoped record.
 */',
         'namespace' => 'App\\Modules\\Onboarding\\Http\\Controllers',
         'uses' => 
        array (
          'applicationdocument' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
          'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
          'stepvalidators' => 'App\\Modules\\Onboarding\\StepValidators',
          'trackcatalog' => 'App\\Modules\\Onboarding\\TrackCatalog',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'storage' => 'Illuminate\\Support\\Facades\\Storage',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'putStep',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'step',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'status',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'uploadDocument',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/ReviewController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Http\\Controllers\\ReviewController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Reviewer surface (stage 7 port): SLA-flagged queue plus explicit verb
 * actions (approve / reject / request-info) so each decision carries its
 * own capability. Rejections require a reason and a countersigning second
 * reviewer; every decision appends to the audit trail (CON-004).
 */',
         'namespace' => 'App\\Modules\\Onboarding\\Http\\Controllers',
         'uses' => 
        array (
          'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
          'reviewrecord' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
          'trackcatalog' => 'App\\Modules\\Onboarding\\TrackCatalog',
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'audit',
               'type' => 'App\\Platform\\Audit\\AuditWriter',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'queue',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'approve',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'reject',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'requestInfo',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Http/Controllers/SubmitController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Http\\Controllers\\SubmitController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Two-phase submit (stage 2 port): begin validates the draft against the
 * pinned track version and issues an OTP challenge; confirm re-validates,
 * verifies the code, and transitions to SUBMITTED. Wrong/expired/
 * exhausted/replayed codes collapse to one error (no oracle).
 */',
         'namespace' => 'App\\Modules\\Onboarding\\Http\\Controllers',
         'uses' => 
        array (
          'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
          'otpratelimitedexception' => 'App\\Modules\\Onboarding\\OtpRateLimitedException',
          'otpservice' => 'App\\Modules\\Onboarding\\OtpService',
          'randomotpcodes' => 'App\\Modules\\Onboarding\\RandomOtpCodes',
          'trackcatalog' => 'App\\Modules\\Onboarding\\TrackCatalog',
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'otp',
               'type' => 'App\\Modules\\Onboarding\\OtpService',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'withRandomCodes',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'missingSteps',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return list<string>
     */',
             'namespace' => 'App\\Modules\\Onboarding\\Http\\Controllers',
             'uses' => 
            array (
              'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
              'otpratelimitedexception' => 'App\\Modules\\Onboarding\\OtpRateLimitedException',
              'otpservice' => 'App\\Modules\\Onboarding\\OtpService',
              'randomotpcodes' => 'App\\Modules\\Onboarding\\RandomOtpCodes',
              'trackcatalog' => 'App\\Modules\\Onboarding\\TrackCatalog',
              'carbonimmutable' => 'Carbon\\CarbonImmutable',
              'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
              'request' => 'Illuminate\\Http\\Request',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'application',
               'type' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'begin',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'confirm',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Models/ApplicationDocument.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Models\\ApplicationDocument',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Modules\\Onboarding\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_PENDING',
               'value' => '\'PENDING\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_VERIFIED',
               'value' => '\'VERIFIED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_REJECTED',
               'value' => '\'REJECTED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Models/OtpChallenge.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Modules\\Onboarding\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Models/PartnerApplication.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Modules\\Onboarding\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
        2 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_DRAFT',
               'value' => '\'DRAFT\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_SUBMITTED',
               'value' => '\'SUBMITTED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_UNDER_REVIEW',
               'value' => '\'UNDER_REVIEW\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_APPROVED',
               'value' => '\'APPROVED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_REJECTED',
               'value' => '\'REJECTED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_APPEALED',
               'value' => '\'APPEALED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_WITHDRAWN',
               'value' => '\'WITHDRAWN\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        8 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        9 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        10 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        11 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return \\Database\\Factories\\PartnerApplicationFactory
     */',
             'namespace' => 'App\\Modules\\Onboarding\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/Models/ReviewRecord.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\Models\\ReviewRecord',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Modules\\Onboarding\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'DECISION_APPROVED',
               'value' => '\'APPROVED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'DECISION_REJECTED',
               'value' => '\'REJECTED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'DECISION_REQUEST_INFO',
               'value' => '\'REQUEST_INFO\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/ModuleServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\ModuleServiceProvider',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Onboarding module provider (doc 01 §2-4): loads the module\'s migrations;
 * routes mount file-based via the registry (routes/api.php), policies land
 * with the reviewer surface.
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
          'basemoduleserviceprovider' => 'App\\Platform\\Modules\\ModuleServiceProvider',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'App\\Platform\\Modules\\ModuleServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'registerModule',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'bootModule',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/OnboardingTrackSeeder.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\OnboardingTrackSeeder',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Supply-track seeding on invite acceptance: pandit/vendor/individual
 * tracks materialize a DRAFT application pinned to the current track
 * version. Customer/staff tracks seed nothing here (customer records land
 * with the customer module; staff needs no profile row).
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
          'partnerapplication' => 'App\\Modules\\Onboarding\\Models\\PartnerApplication',
          'trackseeder' => 'App\\Platform\\Identity\\Contracts\\TrackSeeder',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
        0 => 'App\\Platform\\Identity\\Contracts\\TrackSeeder',
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'seed',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'track',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'identityId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/OtpCodeGenerator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedInterfaceNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\OtpCodeGenerator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * OTP code generation strategy: random in production, deterministic in
 * tests (derived from nothing secret — the hash is what protects codes).
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'extends' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'generate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'length',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'record',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'lastFor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/OtpRateLimitedException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\OtpRateLimitedException',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => true,
       'extends' => 'RuntimeException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/OtpService.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\OtpService',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * OTP issuance + verification (onboarding stage 2 port). Interim home: the
 * identity token program owns challenge delivery later (SMS/voice vendor
 * behind a port); this service keeps policy + verification semantics, which
 * do not change when the sender does.
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
          'otpchallenge' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'CODE_LENGTH',
               'value' => '6',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'TTL_SECONDS',
               'value' => '600',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'MAX_ATTEMPTS',
               'value' => '3',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'MAX_PER_HOUR',
               'value' => '5',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'codes',
               'type' => 'App\\Modules\\Onboarding\\OtpCodeGenerator',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'issue',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Modules\\Onboarding\\Models\\OtpChallenge',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'identifier',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'now',
               'type' => 'Carbon\\CarbonImmutable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'lastCodeFor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'verify',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'now',
               'type' => 'Carbon\\CarbonImmutable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/RandomOtpCodes.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\RandomOtpCodes',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
        0 => 'App\\Modules\\Onboarding\\OtpCodeGenerator',
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'generate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'length',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'lastFor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'record',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/SeededOtpCodes.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\SeededOtpCodes',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
        0 => 'App\\Modules\\Onboarding\\OtpCodeGenerator',
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'generate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'length',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'record',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'lastFor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'challengeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/StepValidators.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\StepValidators',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Per-category evidence validators (field catalog → code). Each category
 * validates its own shape and returns field-keyed reasons; the step PUT
 * route maps these to 422 validation_failed. Predicates stay coarse
 * (present/non-empty for completeness); these enforce shape and format.
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'validate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, list<string>>
     */',
             'namespace' => 'App\\Modules\\Onboarding',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'step',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'value',
               'type' => 'mixed',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Modules/Onboarding/TrackCatalog.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Modules\\Onboarding\\TrackCatalog',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Track catalog (mirrors the Next.js track definitions v1; the control
 * plane owns these records when track-studio lands — this class is the
 * interim source of truth and the shape it must keep).
 */',
         'namespace' => 'App\\Modules\\Onboarding',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'tracks',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, array{version: string, sla_hours: int, steps: list<array{id: string, required: bool}>}>
     */',
             'namespace' => 'App\\Modules\\Onboarding',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'find',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{version: string, sla_hours: int, steps: list<array{id: string, required: bool}>}|null
     */',
             'namespace' => 'App\\Modules\\Onboarding',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => '?array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'track',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'version',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/CodedHttpException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\CodedHttpException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * HTTP exception carrying an explicit error-envelope code (doc 06 §4.2)
 * for cases where the code is not derivable from the status alone
 * (e.g. 403 `state_forbidden` vs 403 `permission_missing`).
 */',
         'namespace' => 'App\\Platform\\Api',
         'uses' => 
        array (
          'httpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'status',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'envelopeCode',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'message',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/Console/ApiComposeCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\Console\\ApiComposeCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan api:compose [--check]` — merge kernel components + module
 * fragments into docs/api/openapi.v1.yaml (api-platform §9.1). Fails on
 * path collisions, duplicate operationIds, and undeclared x-permission
 * codes. --check validates without writing (CI gate).
 */',
         'namespace' => 'App\\Platform\\Api\\Console',
         'uses' => 
        array (
          'openapicomposer' => 'App\\Platform\\Api\\OpenApiComposer',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'command' => 'Illuminate\\Console\\Command',
          'yaml' => 'Symfony\\Component\\Yaml\\Yaml',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'registry',
               'type' => 'App\\Platform\\Modules\\ModuleRegistry',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/CursorPagination.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\CursorPagination',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Cursor pagination for kernel API surfaces (doc 06 §3.3): cursor-only
 * (offset is banned), limit default 25 / max 100, opaque base64 cursor,
 * default ordering created_at DESC, id DESC.
 */',
         'namespace' => 'App\\Platform\\Api',
         'uses' => 
        array (
          'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'request' => 'Illuminate\\Http\\Request',
          'carbon' => 'Illuminate\\Support\\Carbon',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'paginate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return array{0: list<TModel>, 1: array{limit: int, has_more: bool, next_cursor: string|null}}
     */',
             'namespace' => 'App\\Platform\\Api',
             'uses' => 
            array (
              'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'request' => 'Illuminate\\Http\\Request',
              'carbon' => 'Illuminate\\Support\\Carbon',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'query',
               'type' => 'Illuminate\\Database\\Eloquent\\Builder',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'defaultLimit',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/ErrorEnvelope.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\ErrorEnvelope',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * JSON error envelope renderer (doc 06 §4).
 *
 * All non-2xx API responses use exactly:
 *   { "error": { "code", "message", "details", "request_id" } }
 *
 * `code` comes from the kernel-owned registry (doc 06 §4.2); clients branch
 * on `code`, never on `message`. Blade/admin requests keep the framework\'s
 * default HTML error rendering — only JSON-expecting or /api requests are
 * enveloped.
 */',
         'namespace' => 'App\\Platform\\Api',
         'uses' => 
        array (
          'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
          'membershipinactiveexception' => 'App\\Platform\\Tenancy\\Exceptions\\MembershipInactiveException',
          'tenantheadermissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantHeaderMissingException',
          'tenantnotintokenexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantNotInTokenException',
          'tenantsuspendedexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantSuspendedException',
          'authorizationexception' => 'Illuminate\\Auth\\Access\\AuthorizationException',
          'authenticationexception' => 'Illuminate\\Auth\\AuthenticationException',
          'modelnotfoundexception' => 'Illuminate\\Database\\Eloquent\\ModelNotFoundException',
          'throttlerequestsexception' => 'Illuminate\\Http\\Exceptions\\ThrottleRequestsException',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'str' => 'Illuminate\\Support\\Str',
          'validationexception' => 'Illuminate\\Validation\\ValidationException',
          'accessdeniedhttpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException',
          'httpexceptioninterface' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpExceptionInterface',
          'throwable' => 'Throwable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'render',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => '?Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'e',
               'type' => 'Throwable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/OpenApiComposer.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\OpenApiComposer',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * OpenAPI composition (api-platform §9.1): merges kernel shared components
 * with module fragments into one published document.
 *
 * Pure merge + validation over parsed arrays (filesystem stays in the
 * command): path collisions fail, duplicate operationIds fail, and every
 * x-permission code must exist in the declared set. Additive evolution is
 * a review rule on top of these mechanical gates.
 */',
         'namespace' => 'App\\Platform\\Api',
         'uses' => 
        array (
          'yaml' => 'Symfony\\Component\\Yaml\\Yaml',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'compose',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param array<string, mixed> $kernel  Parsed kernel components file.
     * @param list<array{source: string, document: array<string, mixed>}> $fragments
     * @param list<string> $declaredPermissions
     * @return array{document: array<string, mixed>|null, errors: list<string>}
     */',
             'namespace' => 'App\\Platform\\Api',
             'uses' => 
            array (
              'yaml' => 'Symfony\\Component\\Yaml\\Yaml',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'kernel',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'fragments',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'declaredPermissions',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'parseFile',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Api',
             'uses' => 
            array (
              'yaml' => 'Symfony\\Component\\Yaml\\Yaml',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'path',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Api/ResponseMessages.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Api\\ResponseMessages',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Small helper mapping HTTP statuses to canonical messages/reasons so the
 * error envelope never leaks framework internals.
 */',
         'namespace' => 'App\\Platform\\Api',
         'uses' => 
        array (
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'forStatus',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'status',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'reasonPhrase',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'status',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Audit/AuditLog.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Audit\\AuditLog',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Platform\\Audit',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'UPDATED_AT',
               'value' => 'null',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'timestamps',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => true,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getConnectionName',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Tenant data models resolve their connection from the ambient context
     * (TEN-012): the resolver routes dedicated tenants to their own
     * connection. Unresolved contexts fall back to the shared connection;
     * writes still fail closed via BelongsToTenant.
     */',
             'namespace' => 'App\\Platform\\Audit',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Audit/AuditWriter.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Audit\\AuditWriter',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Kernel audit writer (K-025, CON-004): every state change and every
 * authorization denial writes a tenant-scoped audit row with actor,
 * action, resource reference (K-012), before/after payloads, request id
 * and a per-tenant hash chain.
 *
 * Fail-closed: writing outside a resolved TenantContext throws — audit
 * rows never leak across tenants (TEN-009).
 */',
         'namespace' => 'App\\Platform\\Audit',
         'uses' => 
        array (
          'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
          'request' => 'Illuminate\\Http\\Request',
          'auth' => 'Illuminate\\Support\\Facades\\Auth',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'record',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array{module?: string, resource_type?: string, resource_id?: string}|null  $resource
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */',
             'namespace' => 'App\\Platform\\Audit',
             'uses' => 
            array (
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
              'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
              'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
              'request' => 'Illuminate\\Http\\Request',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Audit\\AuditLog',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'action',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'resource',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'before',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'after',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/AuthorizationDecision.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\AuthorizationDecision',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * K-013 AuthorizationDecision: the outcome of one SEC-022 chain
 * evaluation — decision, deciding stage, the evaluated policy ids and
 * the full per-stage trace consumed by the explain surface (T-0K01-02).
 * `logged` is set by the decision log (T-0K01-03), never by the
 * evaluator itself.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
          'invalidargumentexception' => 'InvalidArgumentException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'DECISION_ALLOW',
               'value' => '\'allow\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'DECISION_DENY',
               'value' => '\'deny\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  list<string>  $evaluatedPolicies
     * @param  list<array<string, mixed>>  $stages
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'decision',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'decidingStage',
               'type' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principalId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'evaluatedPolicies',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            5 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'stages',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            6 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'cached',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            7 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'logged',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'allowed',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'withCached',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'cached',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $payload
     *
     * @throws InvalidArgumentException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'payload',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/AuthorizationRequest.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\AuthorizationRequest',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * K-013 AuthorizationRequest: everything the SEC-022 chain needs to
 * decide one capability check — the principal, the capability code, an
 * optional resource reference and Working Context (opaque until
 * T-0K02-02), and the bounded condition attributes (amount, currency,
 * org, time, status).
 *
 * membershipId/principalId may be set explicitly for explain paths that
 * evaluate another principal than the ambient context\'s; both default
 * to the resolved TenantContext at evaluation time.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'KIND_USER',
               'value' => '\'user\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'KIND_AGENT',
               'value' => '\'agent\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'KIND_SERVICE',
               'value' => '\'service\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'KIND_PARTNER',
               'value' => '\'partner\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>|null  $resource
     * @param  array<string, mixed>|null  $workingContext
     * @param  array<string, mixed>  $attributes
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principal',
               'type' => '?Illuminate\\Contracts\\Auth\\Authenticatable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principalKind',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principalId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            5 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'resource',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            6 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'workingContext',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            7 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'attributes',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/AuthzCacheInvalidator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\AuthzCacheInvalidator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Decision-cache invalidation hooks (T-0K01-02.2, AC-T-0K01-02-3): every
 * mutation of authorization state — policy lifecycle changes, capability
 * registry syncs, grant changes — bumps the owning tenant\'s decision
 * generation so cached decisions become unreachable within the same
 * request cycle in which the state changed. Platform-default rows
 * (tenant_id NULL) affect every tenant and bump the global generation.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'cache',
               'type' => 'App\\Platform\\Authz\\DecisionCache',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'invalidateForPolicy',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'invalidateTenant',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/CapabilityAuthorizer.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\CapabilityAuthorizer',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Kernel capability authorizer — the Phase-0K floor of the SEC-022 chain.
 * Resolves: authenticated principal -> active membership (via the resolved
 * TenantContext) -> active-role grants containing the capability code.
 *
 * Fail-closed at every step (CON-003): an unresolved tenant context,
 * missing membership id, or absent grant denies. The full precedence
 * chain (explicit DENY, security constraints, policy conditions,
 * delegation) lands in T-0K01-02; this class is the integration point it
 * extends.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
          'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'allows',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'user',
               'type' => '?Illuminate\\Contracts\\Auth\\Authenticatable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'ensure',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @throws AuthorizationDeniedException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
              'role' => 'App\\Platform\\Authz\\Models\\Role',
              'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
              'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
              'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
              'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'user',
               'type' => '?Illuminate\\Contracts\\Auth\\Authenticatable',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/CapabilityParser.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\CapabilityParser',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Capability parser (SEC-020, T-0K01-01.3): turns a
 * <module>:<resource>:<action> code into structured metadata. Parsing is
 * strict and fail-closed — malformed codes throw CapabilityParseException
 * so sync aborts with a named error code rather than registering garbage.
 *
 * The action vocabulary is open per module (SEC-020): the parser does NOT
 * police the action against a vocabulary here — manifest validation
 * (ManifestValidator) enforces the closed kernel floor + declared
 * extensions at discovery. The parser only enforces shape.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'capability' => 'App\\Platform\\Authz\\Contracts\\Capability',
          'capabilityparseexception' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'SEGMENT_PATTERN',
               'value' => '\'/^[a-z][a-z0-9_]*$/\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'parse',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Parse a concrete capability code (no wildcards).
     *
     * @throws CapabilityParseException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'capability' => 'App\\Platform\\Authz\\Contracts\\Capability',
              'capabilityparseexception' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\Contracts\\Capability',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/ConditionEvaluator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\ConditionEvaluator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Evaluation of the bounded policy condition grammar (SEC-021, LD-15)
 * against a request\'s attribute set. Fail-closed: a condition whose
 * attribute is missing, mistyped, or outside the grammar fails, so a
 * policy whose conditions are not satisfied never matches a request.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'condition' => 'App\\Platform\\Authz\\Contracts\\Condition',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'evaluate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  list<array<string, mixed>>  $conditions
     * @param  array<string, mixed>  $attributes
     * @return array{0: bool, 1: list<array{field: string, operator: string, reason: string}>}
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'condition' => 'App\\Platform\\Authz\\Contracts\\Condition',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'conditions',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'attributes',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Console/AuthzExplainCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Console\\AuthzExplainCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan platform:authz-explain` (T-0K01-02.4): debugging command
 * evaluating the SEC-022 chain for one membership + capability outside a
 * request context. The tenant context is resolved from the membership
 * itself and cleared afterwards (fail-closed on unknown memberships).
 * `--json` emits exactly the payload the GET /api/v1/authz/explain
 * endpoint returns under `data`.
 */',
         'namespace' => 'App\\Platform\\Authz\\Console',
         'uses' => 
        array (
          'authorizationrequest' => 'App\\Platform\\Authz\\AuthorizationRequest',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'command' => 'Illuminate\\Console\\Command',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'engine',
               'type' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principals',
               'type' => 'App\\Platform\\Authz\\PrincipalResolver',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Console/SyncPermissionsCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Console\\SyncPermissionsCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan platform:sync-permissions` — materializes every
 * manifest-declared capability into the structured capability registry
 * (SEC-020, CON-005) and validates every stored policy selector
 * (SEC-021). Fail-closed: any malformed capability code, undeclared
 * action, or invalid policy selector aborts the sync with a named error
 * code BEFORE any write (AC-T-0K01-01-1). `--dry-run` prints the
 * per-tenant diff without touching the database.
 */',
         'namespace' => 'App\\Platform\\Authz\\Console',
         'uses' => 
        array (
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
          'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
          'capabilityparseexception' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'command' => 'Illuminate\\Console\\Command',
          'collection' => 'Illuminate\\Support\\Collection',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'registry',
               'type' => 'App\\Platform\\Modules\\ModuleRegistry',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'parser',
               'type' => 'App\\Platform\\Authz\\CapabilityParser',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/Capability.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\Capability',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * K-004 Capability contract value object.
 *
 * Capabilities are parsed, not opaque strings (SEC-020): every code
 * <module>:<resource>:<action> resolves to structured metadata — module,
 * resource, action — plus version and the sensitive flag. Scopes and
 * conditions are separate dimensions and are never encoded into the
 * identifier.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
          'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'module',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'resource',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'action',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'version',
               'type' => 'int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            5 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'sensitive',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            6 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'description',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromCode',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{code: string, module: string, resource: string, action: string, version: int, sensitive: bool, description: string}
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'capabilityparser' => 'App\\Platform\\Authz\\CapabilityParser',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/CapabilitySelector.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy capability selector (SEC-021, K-009 capability_selector): a
 * module/resource/action pattern where each segment may be a concrete
 * token or the `*` wildcard. Selectors match concrete capability codes at
 * evaluation time (T-0K01-02).
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'module',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'resource',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'action',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $selector
     *
     * @throws InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'selector',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'matches',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Does this selector match a concrete capability code\'s segments?
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'module',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'resource',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'action',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{module: string, resource: string, action: string}
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/Condition.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\Condition',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * One condition clause of the bounded policy condition grammar (SEC-021,
 * LD-15): a closed set of comparison operators over declared fields
 * (amount, currency, org, time, status). Free-form expression languages
 * are banned — anything outside this shape is rejected at validation.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'FIELDS',
               'value' => '[\'amount\', \'currency\', \'org\', \'time\', \'status\']',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'OPERATORS',
               'value' => '[\'eq\', \'neq\', \'gt\', \'gte\', \'lt\', \'lte\', \'in\', \'not_in\']',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'field',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'operator',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'value',
               'type' => 'mixed',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $condition
     *
     * @throws InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'condition',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{field: string, operator: string, value: mixed}
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/DecidingStage.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedEnumNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
       'scalarType' => 'string',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * K-013 AuthorizationDecision reason: which stage of the SEC-022
 * precedence chain decided the outcome. Every decision records its
 * deciding stage (SEC-022: "every decision records which stage decided
 * it"). forged_context is reserved for Working Context forgery denials
 * and lands with T-0K02-02.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'implements' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'ExplicitDeny',
           'value' => '\'explicit_deny\'',
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'AgentSafety',
           'value' => '\'agent_safety\'',
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'SecurityConstraint',
           'value' => '\'security_constraint\'',
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'PolicyConstraint',
           'value' => '\'policy_constraint\'',
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'AllowGrant',
           'value' => '\'allow_grant\'',
           'phpDoc' => NULL,
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'DefaultDeny',
           'value' => '\'default_deny\'',
           'phpDoc' => NULL,
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'ForgedContext',
           'value' => '\'forged_context\'',
           'phpDoc' => NULL,
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/Effect.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedEnumNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\Effect',
       'scalarType' => 'string',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy effect (SEC-021, K-009): allow or deny. Explicit deny always wins
 * in the SEC-022 precedence chain regardless of grant source.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'implements' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'Allow',
           'value' => '\'allow\'',
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'Deny',
           'value' => '\'deny\'',
           'phpDoc' => NULL,
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/PolicyStatus.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedEnumNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
       'scalarType' => 'string',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy lifecycle status. Transitions: draft|disabled -> active
 * (activate), active|draft -> disabled (disable). Anything else is a
 * 409 `state_forbidden` state transition error.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'implements' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'Draft',
           'value' => '\'draft\'',
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'Active',
           'value' => '\'active\'',
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedEnumCaseNode::__set_state(array(
           'name' => 'Disabled',
           'value' => '\'disabled\'',
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'allowedTransitions',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return list<PolicyStatus>
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'canTransitionTo',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'target',
               'type' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Contracts/ScopeBinding.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy / role-assignment scope binding (SEC-021, K-009 scope_bindings).
 * scope_type/scope_id resolve against hierarchy nodes (the hierarchy
 * wiring lands in T-0K02-01); scope_mode is the optional HIER-005
 * mechanism.
 */',
         'namespace' => 'App\\Platform\\Authz\\Contracts',
         'uses' => 
        array (
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'SCOPE_MODES',
               'value' => '[\'SELF\', \'NODE\', \'DIRECT_CHILDREN\', \'SUBTREE\']',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'scopeType',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'scopeId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'scopeMode',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $binding
     *
     * @throws InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'binding',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{scope_type: string, scope_id: string, scope_mode?: string}
     */',
             'namespace' => 'App\\Platform\\Authz\\Contracts',
             'uses' => 
            array (
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/DecisionCache.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\DecisionCache',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * SEC-022 decision cache (T-0K01-02.2, AC-T-0K01-02-3): memoizes chain
 * decisions per (tenant, generation, principal kind, membership,
 * capability, evaluation context). The evaluation context covers the
 * bounded condition attributes plus the resource reference and Working
 * Context so any change in request shape gets its own entry.
 *
 * Invalidation is generation-based: AuthzCacheInvalidator bumps the
 * tenant generation on any policy / grant / registry mutation (platform
 * default rows bump the global generation), so a stale decision becomes
 * unreachable within the same request cycle in which authorization state
 * changed. The short TTL is the belt-and-braces bound required by the
 * task risk register.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'repository' => 'Illuminate\\Contracts\\Cache\\Repository',
          'throwable' => 'Throwable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'cache',
               'type' => 'Illuminate\\Contracts\\Cache\\Repository',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'get',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $context
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'repository' => 'Illuminate\\Contracts\\Cache\\Repository',
              'throwable' => 'Throwable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?App\\Platform\\Authz\\AuthorizationDecision',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principalKind',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'put',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $context
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'repository' => 'Illuminate\\Contracts\\Cache\\Repository',
              'throwable' => 'Throwable',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principalKind',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            5 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'decision',
               'type' => 'App\\Platform\\Authz\\AuthorizationDecision',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'invalidate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'invalidateAll',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Events/PolicyCreated.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Events\\PolicyCreated',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Platform event `platform.policy.created` (AC-T-0K01-01-3): emitted with
 * the full policy payload — including the capability selector — when a
 * policy record is created.
 */',
         'namespace' => 'App\\Platform\\Authz\\Events',
         'uses' => 
        array (
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'App\\Platform\\Authz\\Models\\Policy',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'actorId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'platformEventName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toPlatformPayload',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Authz\\Events',
             'uses' => 
            array (
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Events/PolicyStatusChanged.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Platform event `platform.policy.status_changed` (AC-T-0K01-01-3):
 * emitted with the full policy payload plus the from/to status when a
 * policy transitions draft|active|disabled.
 */',
         'namespace' => 'App\\Platform\\Authz\\Events',
         'uses' => 
        array (
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'App\\Platform\\Authz\\Models\\Policy',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'fromStatus',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'toStatus',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'actorId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'platformEventName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toPlatformPayload',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Authz\\Events',
             'uses' => 
            array (
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Events/PolicyUpdated.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Platform event `platform.policy.updated` (AC-T-0K01-01-3): emitted with
 * the full policy payload when a policy\'s content changes.
 */',
         'namespace' => 'App\\Platform\\Authz\\Events',
         'uses' => 
        array (
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'App\\Platform\\Authz\\Models\\Policy',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'actorId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'platformEventName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toPlatformPayload',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Authz\\Events',
             'uses' => 
            array (
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Exceptions/AuthorizationDeniedException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Kernel authorization denial (doc 06 §4.2, SEC-022).
 *
 * Thrown by the kernel authorizer when a capability check fails. The error
 * envelope renders it as 403 with the stable machine code
 * `authorization_denied`; clients branch on the code, never the message.
 * Carries the denied capability + deciding reason so the denial audit
 * record and (from T-0K01-03) the decision log can be written.
 */',
         'namespace' => 'App\\Platform\\Authz\\Exceptions',
         'uses' => 
        array (
          'authorizationexception' => 'Illuminate\\Auth\\Access\\AuthorizationException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Auth\\Access\\AuthorizationException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capability',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'reason',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'message',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Exceptions/CapabilityParseException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * A capability code failed to parse into <module>:<resource>:<action>
 * (SEC-020). Sync fails closed on this with the named error code
 * `capability_parse_failed` (AC-T-0K01-01-1). The offending code is
 * carried on capabilityCode (not Exception::$code, which PHP reserves
 * for an integer).
 */',
         'namespace' => 'App\\Platform\\Authz\\Exceptions',
         'uses' => 
        array (
          'runtimeexception' => 'RuntimeException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'RuntimeException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'capabilityCode',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'message',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Exceptions/InvalidPolicyPayloadException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * A policy payload violates the bounded declarative grammar (SEC-021,
 * LD-15): bad effect, capability selector shape, scope binding, or
 * condition clause. Rendered by the error envelope as 422
 * `validation_failed` with details.fields keyed by the offending field —
 * clients branch on the code, never the message.
 */',
         'namespace' => 'App\\Platform\\Authz\\Exceptions',
         'uses' => 
        array (
          'runtimeexception' => 'RuntimeException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'RuntimeException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'field',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'message',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Http/Controllers/AuthzExplainController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Http\\Controllers\\AuthzExplainController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Authorization explain surface (T-0K01-02.3, SEC-022): restricted
 * endpoint returning the full precedence-chain trace for one capability
 * evaluation. Mounted behind auth + tenant resolution +
 * `platform:authz:explain` (routes/platform/api.php).
 *
 * Principals are "visible" when their membership belongs to the caller\'s
 * resolved tenant; anything else is denied with `authorization_denied`
 * (AC-T-0K01-02-4). Evaluation bypasses the decision cache so the trace
 * always reflects current authorization state. Deliberately does not
 * extend the app-shell Controller base class so the kernel layer keeps
 * its dependency boundary (doc 01 §7).
 */',
         'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
         'uses' => 
        array (
          'codedhttpexception' => 'App\\Platform\\Api\\CodedHttpException',
          'authorizationrequest' => 'App\\Platform\\Authz\\AuthorizationRequest',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'principalresolver' => 'App\\Platform\\Authz\\PrincipalResolver',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'validator' => 'Illuminate\\Support\\Facades\\Validator',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'engine',
               'type' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'principals',
               'type' => 'App\\Platform\\Authz\\PrincipalResolver',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'explain',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Http/Controllers/PolicyController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Http\\Controllers\\PolicyController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy CRUD admin surface (T-0K01-01.4) at /api/v1/policies (SEC-021).
 *
 * Mounted behind auth + tenant resolution + capability middleware
 * (routes/platform/api.php): `platform:policy:view` for reads,
 * `platform:policy:configure` (sensitive) for mutations. Deliberately does
 * not extend the app-shell Controller base class so the kernel layer keeps
 * its dependency boundary (doc 01 §7).
 */',
         'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
         'uses' => 
        array (
          'codedhttpexception' => 'App\\Platform\\Api\\CodedHttpException',
          'cursorpagination' => 'App\\Platform\\Api\\CursorPagination',
          'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
          'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'policyservice' => 'App\\Platform\\Authz\\PolicyService',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'service',
               'type' => 'App\\Platform\\Authz\\PolicyService',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'show',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'update',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'activate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'disable',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Http/Controllers/RoleController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Http\\Controllers\\RoleController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Role + assignment administration (platform-global surfaces, tenant
 * data underneath): roles are tenant-scoped rows, so every mutation
 * resolves the target tenant\'s context explicitly (body tenant_id +
 * issuer membership check) instead of relying on ambient resolution.
 * SoD rules land with the sod_rules table (spec\'d, unbuilt) — grants
 * are direct until then, fully audited via the decision trail.
 */',
         'namespace' => 'App\\Platform\\Authz\\Http\\Controllers',
         'uses' => 
        array (
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'connectionresolver' => 'App\\Platform\\Tenancy\\ConnectionResolver',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'store',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'grant',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'revoke',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'assignmentId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Http/Middleware/CheckCapability.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Http\\Middleware\\CheckCapability',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Route middleware enforcing a capability requirement (doc 04 §5, SEC-022):
 * `->middleware(\'capability:platform:policy:configure\')`. Every route
 * capability check passes through the full precedence chain (explicit
 * deny, security constraints, policy constraints, allow grant, default
 * deny) via the PolicyEvaluationEngine (T-0K01-02).
 *
 * Laravel splits middleware parameters on `:`, so the capability code\'s
 * segments arrive as separate arguments and are re-joined here. Every
 * denial writes an `authz.denied` audit record (DoD item 4, negative AC
 * paths) before rethrowing; the error envelope renders the stable code
 * `authorization_denied`.
 */',
         'namespace' => 'App\\Platform\\Authz\\Http\\Middleware',
         'uses' => 
        array (
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'authorizationrequest' => 'App\\Platform\\Authz\\AuthorizationRequest',
          'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
          'policyevaluationengine' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'closure' => 'Closure',
          'request' => 'Illuminate\\Http\\Request',
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'engine',
               'type' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'audit',
               'type' => 'App\\Platform\\Audit\\AuditWriter',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Symfony\\Component\\HttpFoundation\\Response',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'next',
               'type' => 'Closure',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'segments',
               'type' => 'string',
               'byRef' => false,
               'variadic' => true,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/CapabilityMetadata.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Capability registry row (SEC-020): the permission registry with
 * structured parse columns. Every <module>:<resource>:<action> code
 * resolves to module/resource/action plus version + sensitive flag.
 * Materialized per tenant by platform:sync-permissions (CON-005) — the
 * sync writes explicit tenant_id values (kernel maintenance path), hence
 * tenant_id is mass-assignable on this model only.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $module
 * @property string $resource
 * @property string $action
 * @property int $version
 * @property bool $sensitive
 * @property string|null $description
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'capabilitymetadatafactory' => 'Database\\Factories\\CapabilityMetadataFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<CapabilityMetadata>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'capabilitymetadatafactory' => 'Database\\Factories\\CapabilityMetadataFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/MembershipRoleAssignment.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Membership -> role assignment (central authorization group, TEN-003).
 * Assignments may carry scope bindings (SEC-021 role-assignment scoping).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $membership_id
 * @property string $role_id
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'membershiproleassignmentfactory' => 'Database\\Factories\\MembershipRoleAssignmentFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<MembershipRoleAssignment>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'membershiproleassignmentfactory' => 'Database\\Factories\\MembershipRoleAssignmentFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'role',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return BelongsTo<Role, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'membershiproleassignmentfactory' => 'Database\\Factories\\MembershipRoleAssignmentFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'scopeBindings',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return HasMany<RoleScopeBinding, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'membershiproleassignmentfactory' => 'Database\\Factories\\MembershipRoleAssignmentFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/Policy.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\Policy',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy record (SEC-021, K-009) on the central authorization group
 * (TEN-003 §2.1). tenant_id NULL marks a platform-default policy:
 * overridable but not silently removable (LD-15) — platform defaults are
 * readable by every tenant (IncludesPlatformDefaults) while mutations go
 * through platform tooling only.
 *
 * tenant_id is mass-assignable so kernel maintenance paths can write
 * explicit tenant scoping and platform-default rows (tenant_id NULL);
 * tenant admin surfaces never accept it — the policy grammar validator
 * rejects undeclared fields (strict validation, doc 06 §3.1).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string $effect
 * @property array<string, mixed> $capability_selector
 * @property list<array<string, mixed>>|null $scope_bindings
 * @property list<array<string, mixed>>|null $conditions
 * @property int $priority
 * @property string $status
 * @property int $version
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'includesplatformdefaults' => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
          'policyfactory' => 'Database\\Factories\\PolicyFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
        0 => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<Policy>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'includesplatformdefaults' => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
              'policyfactory' => 'Database\\Factories\\PolicyFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'includesPlatformDefaults',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'isPlatformDefault',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'statusEnum',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toPayload',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Full policy payload shared by the platform.policy.* events
     * (AC-T-0K01-01-3: events carry the full selector payload).
     *
     * @return array<string, mixed>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'includesplatformdefaults' => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
              'policyfactory' => 'Database\\Factories\\PolicyFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/Role.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\Role',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Role profile (central authorization group, TEN-003). Flat roles with
 * explicit capability grants — no inheritance (SEC register: role
 * hierarchy intentionally omitted).
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $code
 * @property string $name
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'rolefactory' => 'Database\\Factories\\RoleFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_ACTIVE',
               'value' => '\'active\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_DISABLED',
               'value' => '\'disabled\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<Role>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolefactory' => 'Database\\Factories\\RoleFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'permissions',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return HasMany<RolePermission, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolefactory' => 'Database\\Factories\\RoleFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'assignments',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return HasMany<MembershipRoleAssignment, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolefactory' => 'Database\\Factories\\RoleFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/RolePermission.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\RolePermission',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Role -> capability grant row (central authorization group, TEN-003).
 * capability_code references the capability_metadata registry.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $role_id
 * @property string $capability_code
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'rolepermissionfactory' => 'Database\\Factories\\RolePermissionFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<RolePermission>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolepermissionfactory' => 'Database\\Factories\\RolePermissionFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'role',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return BelongsTo<Role, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolepermissionfactory' => 'Database\\Factories\\RolePermissionFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/Models/RoleScopeBinding.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\Models\\RoleScopeBinding',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * SEC-021 role-assignment scoping: binds a role assignment to a scope
 * (scope_type/scope_id resolved against hierarchy nodes; the hierarchy
 * wiring lands in T-0K02-01) with an optional HIER-005 scope mode.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $role_assignment_id
 * @property string $scope_type
 * @property string $scope_id
 * @property string|null $scope_mode
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Authz\\Models',
         'uses' => 
        array (
          'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
          'rolescopebindingfactory' => 'Database\\Factories\\RoleScopeBindingFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'App\\Platform\\Tenancy\\BelongsToTenant',
        1 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        2 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<RoleScopeBinding>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolescopebindingfactory' => 'Database\\Factories\\RoleScopeBindingFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'assignment',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return BelongsTo<MembershipRoleAssignment, $this>
     */',
             'namespace' => 'App\\Platform\\Authz\\Models',
             'uses' => 
            array (
              'belongstotenant' => 'App\\Platform\\Tenancy\\BelongsToTenant',
              'rolescopebindingfactory' => 'Database\\Factories\\RoleScopeBindingFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/PolicyEvaluationEngine.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\PolicyEvaluationEngine',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * SEC-022 precedence-chain evaluator (T-0K01-02.2): deterministic,
 * fail-closed evaluation of one capability request:
 *
 *   1. explicit_deny       - a matching active deny policy whose
 *                            conditions are satisfied denies immediately,
 *                            regardless of grant source.
 *   2. security_constraint - unresolved tenant context, unauthenticated
 *                            principal, missing/inactive membership,
 *                            suspended tenant, machine principal on a
 *                            sensitive capability (SEC-024).
 *   3. policy_constraint   - matching active allow policies exist but none
 *                            is satisfied by the request attributes
 *                            (policies narrow grants).
 *   4. allow_grant         - an active-role grant carries the capability
 *                            for the membership.
 *   5. default_deny        - no explicit allow reached (CON-003).
 *
 * Every decision carries its deciding stage, the evaluated policy ids and
 * the full stage trace consumed by the explain surface (T-0K01-02.3) and,
 * from T-0K01-03, the decision log.
 *
 * Decisions are cached per (tenant, generation, principal kind,
 * membership, capability, evaluation context) with generation-based
 * invalidation (AC-T-0K01-02-3). SEC-024: machine/agent principals are
 * NEVER served from cache — their authorization is re-evaluated on every
 * call.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'capability' => 'App\\Platform\\Authz\\Contracts\\Capability',
          'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
          'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
          'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
          'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
          'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
          'capabilityparseexception' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
          'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
          'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'parser',
               'type' => 'App\\Platform\\Authz\\CapabilityParser',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'conditions',
               'type' => 'App\\Platform\\Authz\\ConditionEvaluator',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'cache',
               'type' => 'App\\Platform\\Authz\\DecisionCache',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'registry',
               'type' => 'App\\Platform\\Modules\\ModuleRegistry',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'evaluate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\AuthorizationDecision',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'App\\Platform\\Authz\\AuthorizationRequest',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'bypassCache',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'ensure',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Evaluate and deny with an AuthorizationDeniedException carrying the
     * deciding stage as the stable reason (middleware + contract calls).
     *
     * @throws AuthorizationDeniedException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'capability' => 'App\\Platform\\Authz\\Contracts\\Capability',
              'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
              'decidingstage' => 'App\\Platform\\Authz\\Contracts\\DecidingStage',
              'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
              'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
              'authorizationdeniedexception' => 'App\\Platform\\Authz\\Exceptions\\AuthorizationDeniedException',
              'capabilityparseexception' => 'App\\Platform\\Authz\\Exceptions\\CapabilityParseException',
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
              'capabilitymetadata' => 'App\\Platform\\Authz\\Models\\CapabilityMetadata',
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
              'role' => 'App\\Platform\\Authz\\Models\\Role',
              'rolepermission' => 'App\\Platform\\Authz\\Models\\RolePermission',
              'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
              'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
              'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
              'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
              'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
              'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\AuthorizationDecision',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'App\\Platform\\Authz\\AuthorizationRequest',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/PolicyService.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\PolicyService',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy lifecycle service (SEC-021, T-0K01-01.4): create / update /
 * status transitions for tenant policies on the admin surface.
 *
 * Every mutation writes a tenant-scoped audit row (DoD item 5) and emits
 * the matching platform.policy.* event with the full selector payload
 * (AC-T-0K01-01-3). Platform-default policies (tenant_id NULL, LD-15) are
 * read-only on tenant surfaces; name uniqueness is per-tenant so tenants
 * can override platform defaults by name without touching them.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'codedhttpexception' => 'App\\Platform\\Api\\CodedHttpException',
          'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
          'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
          'policycreated' => 'App\\Platform\\Authz\\Events\\PolicyCreated',
          'policystatuschanged' => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
          'policyupdated' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
          'policy' => 'App\\Platform\\Authz\\Models\\Policy',
          'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'auth' => 'Illuminate\\Support\\Facades\\Auth',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'validator',
               'type' => 'App\\Platform\\Authz\\PolicyValidator',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'audit',
               'type' => 'App\\Platform\\Audit\\AuditWriter',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'create',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $input
     *
     * @throws Exceptions\\InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'codedhttpexception' => 'App\\Platform\\Api\\CodedHttpException',
              'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
              'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
              'policycreated' => 'App\\Platform\\Authz\\Events\\PolicyCreated',
              'policystatuschanged' => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
              'policyupdated' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
              'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\Models\\Policy',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'input',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'update',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $input
     *
     * @throws Exceptions\\InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'codedhttpexception' => 'App\\Platform\\Api\\CodedHttpException',
              'auditwriter' => 'App\\Platform\\Audit\\AuditWriter',
              'policystatus' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
              'policycreated' => 'App\\Platform\\Authz\\Events\\PolicyCreated',
              'policystatuschanged' => 'App\\Platform\\Authz\\Events\\PolicyStatusChanged',
              'policyupdated' => 'App\\Platform\\Authz\\Events\\PolicyUpdated',
              'policy' => 'App\\Platform\\Authz\\Models\\Policy',
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
              'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
              'auth' => 'Illuminate\\Support\\Facades\\Auth',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\Models\\Policy',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'App\\Platform\\Authz\\Models\\Policy',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'input',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'transitionStatus',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'App\\Platform\\Authz\\Models\\Policy',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'policy',
               'type' => 'App\\Platform\\Authz\\Models\\Policy',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'target',
               'type' => 'App\\Platform\\Authz\\Contracts\\PolicyStatus',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/PolicyValidator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\PolicyValidator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Policy payload validation (SEC-021, LD-15, T-0K01-01.3).
 *
 * Enforces the declarative policy grammar: effect allow|deny, a
 * module/resource/action capability selector (each segment concrete or
 * `*`), scope bindings, and the bounded condition grammar (closed operator
 * set over declared fields). Free-form expression languages are banned —
 * anything outside the grammar throws InvalidPolicyPayloadException, which
 * the API layer renders as 422 `validation_failed`.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'condition' => 'App\\Platform\\Authz\\Contracts\\Condition',
          'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
          'scopebinding' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
          'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
          'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'validateForStore',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Validate a create payload; returns the normalized, storable array.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'condition' => 'App\\Platform\\Authz\\Contracts\\Condition',
              'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
              'scopebinding' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
              'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'input',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'validateForUpdate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Validate a partial update payload (PATCH: absent = unchanged).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws InvalidPolicyPayloadException
     */',
             'namespace' => 'App\\Platform\\Authz',
             'uses' => 
            array (
              'condition' => 'App\\Platform\\Authz\\Contracts\\Condition',
              'effect' => 'App\\Platform\\Authz\\Contracts\\Effect',
              'scopebinding' => 'App\\Platform\\Authz\\Contracts\\ScopeBinding',
              'capabilityselector' => 'App\\Platform\\Authz\\Contracts\\CapabilitySelector',
              'invalidpolicypayloadexception' => 'App\\Platform\\Authz\\Exceptions\\InvalidPolicyPayloadException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'input',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Authz/PrincipalResolver.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Authz\\PrincipalResolver',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Resolves a principal (Authenticatable) from a stored user identifier
 * without the kernel naming a concrete app model (doc 01 §7): the auth
 * user provider is reached through the framework contract only. Used by
 * the explain surface and CLI, which evaluate principals other than the
 * calling user.
 */',
         'namespace' => 'App\\Platform\\Authz',
         'uses' => 
        array (
          'authmanager' => 'Illuminate\\Auth\\AuthManager',
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'resolveById',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?Illuminate\\Contracts\\Auth\\Authenticatable',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'int|string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Console/CleanupExpiredCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Console\\CleanupExpiredCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan cleanup:expired [--dry-run] [--tenant=] [--force]`
 * Retention sweeper (single pass, per class):
 * - OTP challenges past expiry → delete (single-use secrets, no history value).
 * - Invites past expiry → mark EXPIRED (rows retained as audit history).
 * - Web sessions past expiry → delete.
 *
 * Kernel-maintenance bypass (TEN-009): global scopes are lifted explicitly
 * here and only here for sweep purposes; every swept row is tenant-scoped
 * in the report. Destructive runs require --force; dry-run is the default
 * when neither flag is given? No — explicitness wins: --dry-run reports,
 * --force executes, neither errors with usage.
 */',
         'namespace' => 'App\\Platform\\Console',
         'uses' => 
        array (
          'invite' => 'App\\Platform\\Identity\\Models\\Invite',
          'command' => 'Illuminate\\Console\\Command',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Console/DoctorCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Console\\DoctorCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan platform:doctor [--json]` — self-diagnosis for the platform
 * (F2). Fails fast on anything a deploy needs: PHP extensions, APP_KEY,
 * database reachability (central + tenant_shared), Redis reachability,
 * storage writability. Exit 0 = healthy, 1 = at least one check failed.
 * Every check reports ok/fail + detail; --json emits the machine-readable
 * form for pipelines and the future /ready parity check.
 */',
         'namespace' => 'App\\Platform\\Console',
         'uses' => 
        array (
          'command' => 'Illuminate\\Console\\Command',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'runChecks',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return list<array{check: string, ok: bool, detail: string}>
     */',
             'namespace' => 'App\\Platform\\Console',
             'uses' => 
            array (
              'command' => 'Illuminate\\Console\\Command',
              'db' => 'Illuminate\\Support\\Facades\\DB',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Http/Middleware/AttachRequestId.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Http\\Middleware\\AttachRequestId',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Edge middleware generating the request correlation id (doc 05 §11.1).
 *
 * The id propagates to structured logs, audit entries, event envelopes and
 * outgoing webhooks via the `Navdhya-Request-Id` response header and the
 * request attribute. Clients may supply their own id for tracing; it is
 * sanitized to a strict charset.
 */',
         'namespace' => 'App\\Platform\\Http\\Middleware',
         'uses' => 
        array (
          'closure' => 'Closure',
          'request' => 'Illuminate\\Http\\Request',
          'str' => 'Illuminate\\Support\\Str',
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Symfony\\Component\\HttpFoundation\\Response',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'next',
               'type' => 'Closure',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Http/Middleware/LimitJsonBody.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Http\\Middleware\\LimitJsonBody',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * JSON body size cap (process gate, zero runtime risk): rejects JSON
 * request bodies over 1MB with 413 before routing work happens. File
 * uploads ride multipart (never JSON) and keep their own per-endpoint
 * caps; this guard covers accidental or abusive JSON payloads only.
 */',
         'namespace' => 'App\\Platform\\Http\\Middleware',
         'uses' => 
        array (
          'closure' => 'Closure',
          'request' => 'Illuminate\\Http\\Request',
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'MAX_BYTES',
               'value' => '1048576',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Symfony\\Component\\HttpFoundation\\Response',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'next',
               'type' => 'Closure',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/ActorTokenVerifier.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\ActorTokenVerifier',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * HMAC actor-token verification (frontend token bridge, first half).
 *
 * Verifies the opaque bearer tokens minted by the Next.js BFFs:
 * base64url(payload).base64url(HMAC-SHA256) over the payload segment,
 * claims {v,u,s,r[,m,o],e}. Timing-safe compare, expiry enforced,
 * field hygiene (no dots/commas) matching the minter.
 *
 * Second half (Passport JWT + dual-accept window) lands with the token
 * program; this verifier lets Laravel authoritative-check frontend
 * sessions today with zero new dependencies.
 */',
         'namespace' => 'App\\Platform\\Identity',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'verify',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{user_id: string, session_id: string, roles: list<string>, market: ?string, org_id: ?string}|null
     */',
             'namespace' => 'App\\Platform\\Identity',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => '?array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'secret',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'token',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'nowIso',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'base64UrlEncode',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'bytes',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/Contracts/TrackSeeder.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedInterfaceNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Contracts\\TrackSeeder',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Track seeding on invite acceptance (kernel-owned contract, module
 * implementations). The identity layer knows an invite carries a track;
 * what that track materializes (draft application, customer record,
 * nothing) is the owning module\'s decision. Bound by module providers;
 * absent binding means no seeding (fail-open for tracks, never for auth).
 */',
         'namespace' => 'App\\Platform\\Identity\\Contracts',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'extends' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'seed',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>|null Seeded reference (e.g. application
     *   id + track version) or null when the track seeds nothing.
     */',
             'namespace' => 'App\\Platform\\Identity\\Contracts',
             'uses' => 
            array (
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'track',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'identityId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
    )),
  ),
  '../../../app/Platform/Identity/Http/Controllers/IdentitySelfServiceController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Http\\Controllers\\IdentitySelfServiceController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Identity self-service (api-platform §platform surfaces): own profile
 * read/update and web-session inventory with remote revoke.
 *
 * Token-platform sessions (/mfa, OAuth clients) land with the token
 * program; this surface covers database web sessions only. Unknown
 * request fields are rejected (api-platform §3.1 strict validation).
 */',
         'namespace' => 'App\\Platform\\Identity\\Http\\Controllers',
         'uses' => 
        array (
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'profile',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'updateProfile',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'sessions',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'revokeOtherSessions',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/Http/Controllers/InviteController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Http\\Controllers\\InviteController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Track-scoped invites (identity self-service extension): one endpoint
 * family for every persona — track is data, never a code fork. Tokens are
 * SHA-256 hashes (plaintext shown once); acceptance is an atomic,
 * all-or-nothing transaction: user resolve-or-create → membership + role
 * grants → track seed → invite consumed. Supply tracks seed draft
 * applications; unknown roles fail the issue call, never accept.
 */',
         'namespace' => 'App\\Platform\\Identity\\Http\\Controllers',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'membershiproleassignment' => 'App\\Platform\\Authz\\Models\\MembershipRoleAssignment',
          'role' => 'App\\Platform\\Authz\\Models\\Role',
          'trackseeder' => 'App\\Platform\\Identity\\Contracts\\TrackSeeder',
          'invite' => 'App\\Platform\\Identity\\Models\\Invite',
          'connectionresolver' => 'App\\Platform\\Tenancy\\ConnectionResolver',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'issue',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'accept',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'revoke',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/Http/Controllers/UserAdminController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Http\\Controllers\\UserAdminController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * User lifecycle administration (platform-global surfaces, central data):
 * tenant-scoped user listing (via memberships), suspend/activate with
 * reason. Suspended users fail authentication everywhere once the guard
 * check lands with the token program; until then suspension is enforced
 * at membership resolution (suspended state denies entry).
 */',
         'namespace' => 'App\\Platform\\Identity\\Http\\Controllers',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_ACTIVE',
               'value' => '\'active\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_SUSPENDED',
               'value' => '\'suspended\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATUS_DEACTIVATED',
               'value' => '\'deactivated\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'suspend',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'activate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'id',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/Http/Middleware/AuthenticateHmacToken.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Http\\Middleware\\AuthenticateHmacToken',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Session-or-HMAC authentication (token bridge): session clients pass
 * through untouched; otherwise frontend-minted bearer tokens are verified
 * (ActorTokenVerifier) and linked via external_id. Denies with the same
 * AuthenticationException as `auth` when neither succeeds, so routes use
 * this INSTEAD of `auth` (Laravel runs `auth` before custom aliases
 * regardless of listing order — stacking them breaks token clients).
 * Unknown external ids fail closed (no auto-provisioning).
 */',
         'namespace' => 'App\\Platform\\Identity\\Http\\Middleware',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'actortokenverifier' => 'App\\Platform\\Identity\\ActorTokenVerifier',
          'closure' => 'Closure',
          'authenticationexception' => 'Illuminate\\Auth\\AuthenticationException',
          'request' => 'Illuminate\\Http\\Request',
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Symfony\\Component\\HttpFoundation\\Response',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'next',
               'type' => 'Closure',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Identity/Models/Invite.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Identity\\Models\\Invite',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
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
         'namespace' => 'App\\Platform\\Identity\\Models',
         'uses' => 
        array (
          'invitefactory' => 'Database\\Factories\\InviteFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_ISSUED',
               'value' => '\'ISSUED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_ACCEPTED',
               'value' => '\'ACCEPTED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_EXPIRED',
               'value' => '\'EXPIRED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_REVOKED',
               'value' => '\'REVOKED\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'casts',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<Invite>
     */',
             'namespace' => 'App\\Platform\\Identity\\Models',
             'uses' => 
            array (
              'invitefactory' => 'Database\\Factories\\InviteFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/Console/ModuleListCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan module:list` — prints code, version, phase, status and
 * dependencies for operations (doc 01 §2, doc 15 M0).
 */',
         'namespace' => 'App\\Platform\\Modules\\Console',
         'uses' => 
        array (
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'command' => 'Illuminate\\Console\\Command',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'registry',
               'type' => 'App\\Platform\\Modules\\ModuleRegistry',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/Facades/ModuleRegistry.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\Facades\\ModuleRegistry',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * @method static void boot()
 * @method static list<ModuleManifest> modules()
 * @method static ModuleManifest|null find(string $code)
 * @method static bool has(string $code)
 * @method static array<string, array{code: string, name: string, sensitive: bool, module_code: string}> permissions()
 * @method static list<string> scopes()
 * @method static list<string> events()
 * @method static bool enabled(ModuleManifest $module, mixed $tenant = null)
 *
 * @see \\App\\Platform\\Modules\\ModuleRegistry
 */',
         'namespace' => 'App\\Platform\\Modules\\Facades',
         'uses' => 
        array (
          'modulemanifest' => 'App\\Platform\\Modules\\ModuleManifest',
          'facade' => 'Illuminate\\Support\\Facades\\Facade',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Support\\Facades\\Facade',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'getFacadeAccessor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/ManifestValidator.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\ManifestValidator',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Decision-complete manifest validation per doc 01 §3.2/§3.3.
 * Returns the full violation list so boot fails fast with actionable
 * diagnostics instead of one error at a time.
 */',
         'namespace' => 'App\\Platform\\Modules',
         'uses' => 
        array (
          'str' => 'Illuminate\\Support\\Str',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'CORE_ACTIONS',
               'value' => '[\'view\', \'list\', \'create\', \'update\', \'delete\', \'approve\', \'reject\', \'export\', \'configure\']',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Closed action vocabulary (doc 01 §3.3); manifests may extend it via
     * the top-level "actions" field (module-specific verbs).
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'validate',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $manifest
     * @return list<string>
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'manifest',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'directoryName',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/ModuleManifest.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\ModuleManifest',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Immutable value object describing one domain module (doc 01 §3.2) or the
 * synthesized kernel descriptor (doc 05 §2.4 — the kernel has no on-disk
 * module.json; the registry builds it from config/platform.php).
 */',
         'namespace' => 'App\\Platform\\Modules',
         'uses' => 
        array (
          'str' => 'Illuminate\\Support\\Str',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param  array<string, mixed>  $manifest
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'manifest',
               'type' => 'array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'path',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'kernel',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'providerClass',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'routesPath',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Absolute path of the module\'s route file for the given channel
     * ("web" | "api"). Kernel module and modules without the file simply
     * return a non-existent path; callers check with is_file() (doc 01 §1).
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'str' => 'Illuminate\\Support\\Str',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'type',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/ModuleRegistry.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\ModuleRegistry',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Kernel module registry (doc 01 §2).
 *
 * Responsibilities:
 *  1. Discover modules by scanning app/Modules/{Module}/module.json.
 *  2. Validate manifests against doc 01 §3.2/§3.3 (fail fast, boot error).
 *  3. Resolve the dependency graph; cycles are a boot error.
 *  4. Expose query APIs consumed by the kernel and tooling: modules(),
 *     permissions(), scopes(), events(), enabled(tenant) — the latter is
 *     feature-flag gated and wired in M1 once FeatureFlags lands.
 *
 * The kernel itself has no on-disk manifest; it is synthesized from
 * config/platform.php (\'kernel_module\') so dependency graphs may reference
 * the \'platform\' module code (doc 05 §2.4).
 */',
         'namespace' => 'App\\Platform\\Modules',
         'uses' => 
        array (
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'app',
               'type' => 'Illuminate\\Contracts\\Foundation\\Application',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'validator',
               'type' => 'App\\Platform\\Modules\\ManifestValidator',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Discover, validate and topologically order all modules. Safe to call
     * repeatedly; discovery runs once per process.
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'modules',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return list<ModuleManifest> Dependency-ordered, kernel first.
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'find',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?App\\Platform\\Modules\\ModuleManifest',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'has',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'code',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'permissions',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * All declared permissions across every module, keyed by code.
     *
     * @return array<string, array{code: string, name: string, sensitive: bool, module_code: string}>
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'scopes',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * All declared non-trivial scopes (doc 04 §6: read/write are implicit).
     *
     * @return list<string>
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'events',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * All declared event names across every module.
     *
     * @return list<string>
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        8 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'enabled',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Feature-flag gate for per-tenant module enablement (doc 01 §2-5).
     * The FeatureFlags kernel service lands in M3; until then a module with
     * no feature_flag is always enabled and a flagged module defaults to
     * enabled (default-safe branch, doc 05 §10.2 rule 1).
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'application' => 'Illuminate\\Contracts\\Foundation\\Application',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'module',
               'type' => 'App\\Platform\\Modules\\ModuleManifest',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => '?mixed',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Modules/ModuleServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Modules\\ModuleServiceProvider',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Base class every domain module extends (doc 01 §2-4).
 *
 * Modules override the template methods below; the kernel calls them in
 * dependency order during boot. Modules MUST NOT re-register kernel services
 * and MUST route all cross-module communication through contracts + events
 * (doc 01 §5).
 */',
         'namespace' => 'App\\Platform\\Modules',
         'uses' => 
        array (
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => true,
       'final' => false,
       'extends' => 'Illuminate\\Support\\ServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'code',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The module\'s manifest code; resolved from the class namespace by
     * default (App\\Modules\\Sales -> sales). Override to customize.
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'registerModule',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Register module-scoped container bindings. Called during register().
     * Bind contract interfaces to implementations here (doc 01 §3.1).
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'bootModule',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Boot module routes, migrations, policies, listeners and event
     * schemas. Called during boot() in dependency order.
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'register',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'loadModuleMigrations',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Load this module\'s migrations from its Database/Migrations directory
     * (doc 01 §3.1, doc 02 §8: per-module migrations).
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'registerModulePolicies',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Register this module\'s Eloquent policies from Policies/ (doc 01 §3.3:
     * <Entity>Policy). M1 wires the Policy discovery gate.
     */',
             'namespace' => 'App\\Platform\\Modules',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'modulePath',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'relative',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Observability/Health/ReadinessCheck.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Observability\\Health\\ReadinessCheck',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `/ready` readiness probe (doc 05 §11.2, doc 06 §11).
 *
 * Checks (each degrades independently; any failure => 503, node leaves LB):
 *  - central DB ping
 *  - tenant shared DB ping
 *  - Redis/cache ping (production cache store)
 *  - queue connectivity probe
 *  - outbox relay heartbeat age < 120 s (M3 — stubbed until the outbox lands;
 *    the check is wired through a KernelHealth contract so M3 replaces the
 *    stub without touching this controller)
 *
 * `/up` (process alive, no dependencies) ships with the framework health
 * route in bootstrap/app.php.
 */',
         'namespace' => 'App\\Platform\\Observability\\Health',
         'uses' => 
        array (
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'throwable' => 'Throwable',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__invoke',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/BelongsToTenant.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedTraitNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\BelongsToTenant',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * BelongsToTenant kernel trait (doc 02 §5.1, TEN-009).
 *
 * Applied to every tenant-scoped model:
 *  - creating: fills tenant_id from TenantContext; throws when the context
 *    is unresolved (fail-closed). An explicitly present tenant_id attribute
 *    (kernel maintenance / platform-default rows with NULL tenant) is
 *    respected; every such bypass site is architecture-tested.
 *  - global scope: TenantScope binds tenant_id from context on every read,
 *    update and delete (fail-closed when unresolved).
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
          'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
        ),
         'constUses' => 
        array (
        ),
      )),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'bootBelongsToTenant',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/ConnectionResolver.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\ConnectionResolver',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Connection resolver (TEN-012): maps a tenant to its data connection.
 *
 * - `shared` → `tenant_shared`.
 * - `dedicated` → decrypts `db_config_ciphertext` (APP_KEY today, vault
 *   path per security baseline §3), registers a runtime connection
 *   `tenant_ded_<id>`, returns it. Schema is identical in both modes.
 * - Anything else fails closed: unknown db_mode never defaults.
 *
 * db_mode reads are cached 60s, busted on `platform.tenant.promoted`.
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
          'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'cache' => 'Illuminate\\Support\\Facades\\Cache',
          'crypt' => 'Illuminate\\Support\\Facades\\Crypt',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'SHARED_CONNECTION',
               'value' => '\'tenant_shared\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'for',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'cachedFor',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'bust',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantAdoptLegacyCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenant:adopt-legacy --owner= [--slug=] [--dry-run]`
 * (pre-era doctrine): adopt a single-tenant dataset into the multitenant
 * world. Provisions a `legacy` tenant, binds the owner, attributes every
 * NULL-tenant_id row to it, and verifies zero NULLs remain.
 *
 * Attribution, never fabrication: adopted rows point at the legacy tenant
 * and the command reports per-table counts, so operators can always tell
 * born-multitenant rows from adopted ones. Backed-out by restoring the
 * pre-freeze backup (there is no other copy — backup is mandatory and the
 * command refuses without --i-have-a-backup outside --dry-run).
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantpromotion' => 'App\\Platform\\Tenancy\\TenantPromotion',
          'command' => 'Illuminate\\Console\\Command',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'promotion',
               'type' => 'App\\Platform\\Tenancy\\TenantPromotion',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantBackfillV2Command.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenants:backfill-v2 [--dry-run]` — one-time data migration
 * for the TEN-005 amendment (C2): copies legacy `code`→`slug` and
 * `state`→`status` where the new columns are empty.
 *
 * Idempotent and auditable: reports examined/copied/skipped counts, writes
 * nothing under --dry-run, and aborts loudly on slug collisions instead of
 * overwriting.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'command' => 'Illuminate\\Console\\Command',
          'db' => 'Illuminate\\Support\\Facades\\DB',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantDemoteCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantDemoteCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenant:demote --tenant=` (TEN-014): reverse a cutover inside
 * the rollback window by flipping db_mode back to shared. Shared rows are
 * retained, so no copy is needed — the resolver routes back immediately.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantpromotion' => 'App\\Platform\\Tenancy\\TenantPromotion',
          'command' => 'Illuminate\\Console\\Command',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'promotion',
               'type' => 'App\\Platform\\Tenancy\\TenantPromotion',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPromoteCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenant:promote --tenant= --target= [--dry-run]` (TEN-013):
 * freeze (state=promoting, writes rejected) → chunked backfill with
 * checksums → verify → cutover transaction → smoke. Dry-run copies and
 * verifies nothing; every other step is logged.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantpromotion' => 'App\\Platform\\Tenancy\\TenantPromotion',
          'command' => 'Illuminate\\Console\\Command',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'promotion',
               'type' => 'App\\Platform\\Tenancy\\TenantPromotion',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantProvisionSchemaCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantProvisionSchemaCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenant:provision-schema --connection=` — run all loaded
 * module + kernel migrations on the named connection (dedicated tenant
 * databases, scratch targets, promotion destinations). The single
 * supported way to create tenant schema anywhere besides the default
 * migrate path; promotion refuses targets that skipped this step.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'command' => 'Illuminate\\Console\\Command',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'schema' => 'Illuminate\\Support\\Facades\\Schema',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Console/TenantPurgeSharedCommand.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Console\\TenantPurgeSharedCommand',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * `php artisan tenant:purge-shared --tenant= [--force]` (TEN-014): delete
 * the tenant\'s retained shared rows after the rollback window. Refuses
 * without --force; reports the deleted count for the audit trail.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Console',
         'uses' => 
        array (
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantpromotion' => 'App\\Platform\\Tenancy\\TenantPromotion',
          'command' => 'Illuminate\\Console\\Command',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Console\\Command',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'signature',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'description',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'promotion',
               'type' => 'App\\Platform\\Tenancy\\TenantPromotion',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Exceptions/MembershipInactiveException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Exceptions\\MembershipInactiveException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * The authenticated user has no active membership in the requested tenant
 * (TEN-006). Rendered as 403 `membership_inactive`.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
         'uses' => 
        array (
          'httpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'state',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantContextMissingException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Thrown when tenant-scoped state is written or required outside a resolved
 * TenantContext (doc 02 §4 fail-closed, TEN-006/TEN-009). Callers MUST treat
 * this as a hard denial — never default to "all tenants".
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
         'uses' => 
        array (
          'runtimeexception' => 'RuntimeException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'RuntimeException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'operation',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantHeaderMissingException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantHeaderMissingException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Tenant-scoped API request without the X-Tenant-Id header (TEN-006).
 * Rendered by the error envelope as 400 `tenant_header_missing`.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
         'uses' => 
        array (
          'badrequesthttpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Symfony\\Component\\HttpKernel\\Exception\\BadRequestHttpException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantNotInTokenException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantNotInTokenException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * The requested tenant is not valid for the presented credential
 * (TEN-006 fail-closed). Rendered as 401 `tenant_not_in_token`.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
         'uses' => 
        array (
          'httpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Exceptions/TenantSuspendedException.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Exceptions\\TenantSuspendedException',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * The resolved tenant is suspended (TEN-015 lifecycle states).
 * Rendered as 403 `tenant_suspended`.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Exceptions',
         'uses' => 
        array (
          'httpexception' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Symfony\\Component\\HttpKernel\\Exception\\HttpException',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Http/Controllers/TenantSelfServiceController.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Http\\Controllers\\TenantSelfServiceController',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Tenant self-service surface (TEN-006 §4.3, platform-global): the
 * authenticated user\'s own memberships with tenant summaries.
 *
 * Platform-global exemption: mounted WITHOUT the `tenant` middleware
 * (this endpoint is how clients discover which tenants they can enter).
 * Only active memberships resolve a context elsewhere; here all states
 * are listed so clients can explain denials. Never leaks other users\'
 * memberships: scoped to the authenticated principal, always.
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Http\\Controllers',
         'uses' => 
        array (
          'cursorpagination' => 'App\\Platform\\Api\\CursorPagination',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'jsonresponse' => 'Illuminate\\Http\\JsonResponse',
          'request' => 'Illuminate\\Http\\Request',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'index',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Http\\JsonResponse',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Http/Middleware/ResolveTenantContext.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Http\\Middleware\\ResolveTenantContext',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Tenant resolution for API surfaces (doc 02 §4, TEN-006): resolves the
 * ambient TenantContext from the X-Tenant-Id header, validated against the
 * authenticated principal\'s memberships. Fail-closed: a missing header,
 * unknown or suspended tenant, or missing/inactive membership each deny
 * with a stable envelope code (tenant_header_missing / tenant_not_in_token
 * / tenant_suspended / membership_inactive).
 *
 * Typed against the Authenticatable contract (never a concrete app model)
 * so the kernel layer keeps its dependency boundary (doc 01 §7).
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Http\\Middleware',
         'uses' => 
        array (
          'connectionresolver' => 'App\\Platform\\Tenancy\\ConnectionResolver',
          'membershipinactiveexception' => 'App\\Platform\\Tenancy\\Exceptions\\MembershipInactiveException',
          'tenantheadermissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantHeaderMissingException',
          'tenantnotintokenexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantNotInTokenException',
          'tenantsuspendedexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantSuspendedException',
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'tenantmembership' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'closure' => 'Closure',
          'authenticatable' => 'Illuminate\\Contracts\\Auth\\Authenticatable',
          'request' => 'Illuminate\\Http\\Request',
          'response' => 'Symfony\\Component\\HttpFoundation\\Response',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'context',
               'type' => 'App\\Platform\\Tenancy\\TenantContext',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'connections',
               'type' => 'App\\Platform\\Tenancy\\ConnectionResolver',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 68,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'handle',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Symfony\\Component\\HttpFoundation\\Response',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'next',
               'type' => 'Closure',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'terminate',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'request',
               'type' => 'Illuminate\\Http\\Request',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'response',
               'type' => 'Symfony\\Component\\HttpFoundation\\Response',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/IncludesPlatformDefaults.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedInterfaceNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\IncludesPlatformDefaults',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Marks tenant-scoped models that also expose platform-default rows
 * (tenant_id NULL) which are readable by every tenant (LD-15 platform
 * defaults). Mutating platform-default rows still requires platform-level
 * authority and is rejected on the tenant admin surfaces.
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
        ),
         'constUses' => 
        array (
        ),
      )),
       'extends' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'includesPlatformDefaults',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Models/Tenant.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Models\\Tenant',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Tenant registry record (central connection, TEN-005). Central tables
 * carry tenant_id as data, not as a row-level scope: isolation of tenant
 * business data is enforced by the resolution middleware (TEN-006) and the
 * BelongsToTenant scope on tenant tables (TEN-009).
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property string $db_mode
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 * @property-read \\Illuminate\\Database\\Eloquent\\Collection<int, TenantMembership> $memberships
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Models',
         'uses' => 
        array (
          'tenantfactory' => 'Database\\Factories\\TenantFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_ACTIVE',
               'value' => '\'active\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_SUSPENDED',
               'value' => '\'suspended\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_PROMOTING',
               'value' => '\'promoting\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_DEPROVISIONED',
               'value' => '\'deprovisioned\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<Tenant>
     */',
             'namespace' => 'App\\Platform\\Tenancy\\Models',
             'uses' => 
            array (
              'tenantfactory' => 'Database\\Factories\\TenantFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'memberships',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return HasMany<TenantMembership, $this>
     */',
             'namespace' => 'App\\Platform\\Tenancy\\Models',
             'uses' => 
            array (
              'tenantfactory' => 'Database\\Factories\\TenantFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'hasmany' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/Models/TenantMembership.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\Models\\TenantMembership',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * User<->tenant membership (central, K-002). State gates tenant entry
 * (TEN-006 fail-closed): only active memberships resolve a TenantContext.
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string|null $org_node_id
 * @property string $state
 * @property \\Illuminate\\Support\\Carbon|null $created_at
 * @property \\Illuminate\\Support\\Carbon|null $updated_at
 */',
         'namespace' => 'App\\Platform\\Tenancy\\Models',
         'uses' => 
        array (
          'user' => 'App\\Models\\User',
          'tenantmembershipfactory' => 'Database\\Factories\\TenantMembershipFactory',
          'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
          'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
          'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Database\\Eloquent\\Model',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
        1 => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_ACTIVE',
               'value' => '\'active\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_SUSPENDED',
               'value' => '\'suspended\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'STATE_EXPIRED',
               'value' => '\'expired\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'connection',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'fillable',
          ),
           'phpDoc' => NULL,
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'newFactory',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return Factory<TenantMembership>
     */',
             'namespace' => 'App\\Platform\\Tenancy\\Models',
             'uses' => 
            array (
              'user' => 'App\\Models\\User',
              'tenantmembershipfactory' => 'Database\\Factories\\TenantMembershipFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => false,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'tenant',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return BelongsTo<Tenant, $this>
     */',
             'namespace' => 'App\\Platform\\Tenancy\\Models',
             'uses' => 
            array (
              'user' => 'App\\Models\\User',
              'tenantmembershipfactory' => 'Database\\Factories\\TenantMembershipFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'user',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return BelongsTo<User, $this>
     */',
             'namespace' => 'App\\Platform\\Tenancy\\Models',
             'uses' => 
            array (
              'user' => 'App\\Models\\User',
              'tenantmembershipfactory' => 'Database\\Factories\\TenantMembershipFactory',
              'hasulids' => 'Illuminate\\Database\\Eloquent\\Concerns\\HasUlids',
              'factory' => 'Illuminate\\Database\\Eloquent\\Factories\\Factory',
              'hasfactory' => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
              'model' => 'Illuminate\\Database\\Eloquent\\Model',
              'belongsto' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/TenantContext.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\TenantContext',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * TenantContext — the kernel\'s ambient tenancy state (doc 02 §4, TEN-007).
 *
 * Holds the resolved tenant id, active membership, org, connection name and
 * actor for the current request/job. Fail-closed: any code reading the
 * context outside a resolved scope MUST receive null and deny, never
 * default to "all tenants". Set exactly once per scope: a second set()
 * without an intervening clear() throws (programming error, never silent
 * overwrite).
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
          'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'set',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @param array<string, mixed>|null $actor
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'membershipId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'orgId',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'connectionName',
               'type' => '?string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'actor',
               'type' => '?array',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => true,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'clear',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'isResolved',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'tenantId',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Active tenant id; null outside a resolved scope (fail-closed callers
     * treat null as "deny").
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'membershipId',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'orgId',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'connectionName',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'actor',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array<string, mixed>|null
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenantcontextmissingexception' => 'App\\Platform\\Tenancy\\Exceptions\\TenantContextMissingException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => '?array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/TenantPromotion.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\TenantPromotion',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Tenant promotion engine (TEN-013): shared → dedicated with
 * freeze/backfill/verify/cutover/smoke, plus demote and purge-shared.
 * Every step is idempotent and resumable; dry-run touches nothing.
 *
 * Table discovery: any table on the source connection carrying a
 * tenant_id column is in scope (no hardcoded table list to rot).
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
          'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'schema' => 'Illuminate\\Support\\Facades\\Schema',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'CHUNK_SIZE',
               'value' => '5000',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => NULL,
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'tenantTables',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return list<string>
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
              'db' => 'Illuminate\\Support\\Facades\\DB',
              'schema' => 'Illuminate\\Support\\Facades\\Schema',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'connection',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'checksum',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{rows: int, checksum: string}
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
              'db' => 'Illuminate\\Support\\Facades\\DB',
              'schema' => 'Illuminate\\Support\\Facades\\Schema',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'connection',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'table',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'copyTable',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{table: string, copied: int}
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
              'db' => 'Illuminate\\Support\\Facades\\DB',
              'schema' => 'Illuminate\\Support\\Facades\\Schema',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'source',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'target',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'table',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            3 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenantId',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            4 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'dryRun',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'promote',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{tables: list<string>, copied: int, verified: bool, dry_run: bool}
     */',
             'namespace' => 'App\\Platform\\Tenancy',
             'uses' => 
            array (
              'tenant' => 'App\\Platform\\Tenancy\\Models\\Tenant',
              'db' => 'Illuminate\\Support\\Facades\\DB',
              'schema' => 'Illuminate\\Support\\Facades\\Schema',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'targetConnection',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            2 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'dryRun',
               'type' => 'bool',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'cutover',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'targetConnection',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'demote',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'purgeShared',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'tenant',
               'type' => 'App\\Platform\\Tenancy\\Models\\Tenant',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Platform/Tenancy/TenantScope.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Platform\\Tenancy\\TenantScope',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Global scope enforcing row-level tenant isolation (doc 02 §5, TEN-009).
 *
 * Bound from the ambient TenantContext on every read/update/delete:
 *  - resolved context   -> WHERE tenant_id = <context>
 *  - unresolved context -> fail-closed: matches NO rows, never "all tenants".
 *
 * Models exposing platform-default rows (tenant_id NULL — e.g. the policy
 * catalog\'s platform defaults, LD-15) implement IncludesPlatformDefaults so
 * NULL-tenant rows stay visible to every tenant while cross-tenant rows
 * remain hidden.
 */',
         'namespace' => 'App\\Platform\\Tenancy',
         'uses' => 
        array (
          'builder' => 'Illuminate\\Database\\Eloquent\\Builder',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'scope' => 'Illuminate\\Database\\Eloquent\\Scope',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
        0 => 'Illuminate\\Database\\Eloquent\\Scope',
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'apply',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'builder',
               'type' => 'Illuminate\\Database\\Eloquent\\Builder',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'model',
               'type' => 'Illuminate\\Database\\Eloquent\\Model',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/AppServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\AppServiceProvider',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Support\\ServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'register',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Register any application services.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Bootstrap any application services.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/AuthServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\AuthServiceProvider',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Foundation\\Support\\Providers\\AuthServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'policies',
          ),
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\AuthServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Register any authentication / authorization services.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\AuthServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/BroadcastServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\BroadcastServiceProvider',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Support\\ServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Bootstrap any application services.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'broadcast' => 'Illuminate\\Support\\Facades\\Broadcast',
              'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/EventServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\EventServiceProvider',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedPropertiesNode::__set_state(array(
           'names' => 
          array (
            0 => 'listen',
          ),
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'registered' => 'Illuminate\\Auth\\Events\\Registered',
              'sendemailverificationnotification' => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider',
              'event' => 'Illuminate\\Support\\Facades\\Event',
            ),
             'constUses' => 
            array (
            ),
          )),
           'type' => NULL,
           'public' => false,
           'private' => false,
           'static' => false,
           'readonly' => false,
           'abstract' => false,
           'final' => false,
           'publicSet' => false,
           'protectedSet' => false,
           'privateSet' => false,
           'virtual' => false,
           'attributes' => 
          array (
          ),
           'hooks' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Register any events for your application.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'registered' => 'Illuminate\\Auth\\Events\\Registered',
              'sendemailverificationnotification' => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider',
              'event' => 'Illuminate\\Support\\Facades\\Event',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'shouldDiscoverEvents',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Determine if events and listeners should be automatically discovered.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'registered' => 'Illuminate\\Auth\\Events\\Registered',
              'sendemailverificationnotification' => 'Illuminate\\Auth\\Listeners\\SendEmailVerificationNotification',
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\EventServiceProvider',
              'event' => 'Illuminate\\Support\\Facades\\Event',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/PlatformServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\PlatformServiceProvider',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Boots the platform kernel module system (doc 01 §2) and the Phase 0K
 * authorization services: the ambient TenantContext (singleton — the
 * scope, authorizer, audit writer and resolution middleware MUST share one
 * instance), the capability parser (SEC-020), the policy grammar
 * validator (SEC-021/LD-15), the audit writer (K-025), the capability
 * authorizer (SEC-022 floor) and the SEC-022 precedence-chain engine with
 * its generation-based decision cache (T-0K01-02).
 */',
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
          'tenantadoptlegacycommand' => 'App\\Platform\\Tenancy\\Console\\TenantAdoptLegacyCommand',
          'tenantbackfillv2command' => 'App\\Platform\\Tenancy\\Console\\TenantBackfillV2Command',
          'tenantprovisionschemacommand' => 'App\\Platform\\Tenancy\\Console\\TenantProvisionSchemaCommand',
          'tenantdemotecommand' => 'App\\Platform\\Tenancy\\Console\\TenantDemoteCommand',
          'tenantpromotecommand' => 'App\\Platform\\Tenancy\\Console\\TenantPromoteCommand',
          'tenantpurgesharedcommand' => 'App\\Platform\\Tenancy\\Console\\TenantPurgeSharedCommand',
          'modulelistcommand' => 'App\\Platform\\Modules\\Console\\ModuleListCommand',
          'apicomposecommand' => 'App\\Platform\\Api\\Console\\ApiComposeCommand',
          'cleanupexpiredcommand' => 'App\\Platform\\Console\\CleanupExpiredCommand',
          'doctorcommand' => 'App\\Platform\\Console\\DoctorCommand',
          'manifestvalidator' => 'App\\Platform\\Modules\\ManifestValidator',
          'moduleregistry' => 'App\\Platform\\Modules\\ModuleRegistry',
          'tenantcontext' => 'App\\Platform\\Tenancy\\TenantContext',
          'application' => 'Illuminate\\Contracts\\Foundation\\Application',
          'model' => 'Illuminate\\Database\\Eloquent\\Model',
          'serviceprovider' => 'Illuminate\\Support\\ServiceProvider',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => 'Illuminate\\Support\\ServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'register',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Providers/RouteServiceProvider.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Providers\\RouteServiceProvider',
       'phpDoc' => NULL,
       'abstract' => false,
       'final' => false,
       'extends' => 'Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider',
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedClassConstantsNode::__set_state(array(
           'constants' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::__set_state(array(
               'name' => 'HOME',
               'value' => '\'/home\'',
               'attributes' => 
              array (
              ),
            )),
          ),
           'public' => true,
           'private' => false,
           'final' => false,
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * The path to your application\'s "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'limit' => 'Illuminate\\Cache\\RateLimiting\\Limit',
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'route' => 'Illuminate\\Support\\Facades\\Route',
            ),
             'constUses' => 
            array (
            ),
          )),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'boot',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Define your route model bindings, pattern filters, and other route configuration.
     */',
             'namespace' => 'App\\Providers',
             'uses' => 
            array (
              'limit' => 'Illuminate\\Cache\\RateLimiting\\Limit',
              'serviceprovider' => 'Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider',
              'request' => 'Illuminate\\Http\\Request',
              'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
              'route' => 'Illuminate\\Support\\Facades\\Route',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'void',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
  '../../../app/Support/Money.php' => 
  array (
    0 => 
    \PHPStan\Dependency\ExportedNode\ExportedClassNode::__set_state(array(
       'name' => 'App\\Support\\Money',
       'phpDoc' => 
      \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
         'phpDocString' => '/**
 * Money value object (doc 02 §9).
 *
 * Storage: DECIMAL(18,4) + ISO-4217 CHAR(3). Arithmetic: bcmath with
 * 4-decimal fixed-point, half-up rounding on finalize. Amounts are held
 * internally as minor-unit strings (integer strings, no separators) so
 * comparisons are exact; `toDecimal()` renders the 4-dp storage form.
 *
 * Rules (normative):
 *  - All operations require the same currency; mixed-currency math throws.
 *  - Immutable: every operation returns a new instance.
 *  - No float round-trips anywhere in the platform.
 */',
         'namespace' => 'App\\Support',
         'uses' => 
        array (
          'invalidargumentexception' => 'InvalidArgumentException',
        ),
         'constUses' => 
        array (
        ),
      )),
       'abstract' => false,
       'final' => true,
       'extends' => NULL,
       'implements' => 
      array (
      ),
       'usedTraits' => 
      array (
      ),
       'traitUseAdaptations' => 
      array (
      ),
       'statements' => 
      array (
        0 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => '__construct',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => NULL,
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'minorUnits',
               'type' => 'string|int',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'currency',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 65,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        1 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'fromDecimal',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Construct from a decimal string like "1549.9" or "1549.9000".
     */',
             'namespace' => 'App\\Support',
             'uses' => 
            array (
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'decimal',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
            1 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'currency',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        2 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'zero',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => true,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'currency',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        3 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'minorUnits',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        4 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'isZero',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        5 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'isNegative',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        6 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'isPositive',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        7 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'add',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'other',
               'type' => 'self',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        8 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'subtract',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'other',
               'type' => 'self',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        9 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'multiply',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * Multiply by a scalar factor given as a decimal string (e.g. "1.18").
     * Result is rounded half-up to minor units.
     */',
             'namespace' => 'App\\Support',
             'uses' => 
            array (
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'self',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'factor',
               'type' => 'string',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        10 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'compareTo',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'int',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'other',
               'type' => 'self',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        11 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'equals',
           'phpDoc' => NULL,
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'bool',
           'parameters' => 
          array (
            0 => 
            \PHPStan\Dependency\ExportedNode\ExportedParameterNode::__set_state(array(
               'name' => 'other',
               'type' => 'self',
               'byRef' => false,
               'variadic' => false,
               'hasDefault' => false,
               'attributes' => 
              array (
              ),
               'phpDoc' => NULL,
               'flags' => 0,
            )),
          ),
           'attributes' => 
          array (
          ),
        )),
        12 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toDecimal',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * DECIMAL(18,4) storage representation ("1549.9000").
     */',
             'namespace' => 'App\\Support',
             'uses' => 
            array (
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'string',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
        13 => 
        \PHPStan\Dependency\ExportedNode\ExportedMethodNode::__set_state(array(
           'name' => 'toArray',
           'phpDoc' => 
          \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::__set_state(array(
             'phpDocString' => '/**
     * @return array{amount_minor: string, currency: string}
     */',
             'namespace' => 'App\\Support',
             'uses' => 
            array (
              'invalidargumentexception' => 'InvalidArgumentException',
            ),
             'constUses' => 
            array (
            ),
          )),
           'byRef' => false,
           'public' => true,
           'private' => false,
           'abstract' => false,
           'final' => false,
           'static' => false,
           'returnType' => 'array',
           'parameters' => 
          array (
          ),
           'attributes' => 
          array (
          ),
        )),
      ),
       'attributes' => 
      array (
      ),
    )),
  ),
); },
];
