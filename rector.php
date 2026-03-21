<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/config',
		__DIR__ . '/src',
		__DIR__ . '/tests',
	])
	->withSkip([
		RemoveEmptyClassMethodRector::class,
		RemoveUnusedConstructorParamRector::class,
	])
	->withParallel(360)
	->withImportNames(importShortClasses: false, removeUnusedImports: true)
	->withPreparedSets(deadCode: true)
	->withPhpSets();
