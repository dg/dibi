<?php

/**
 * Rules for Nette Coding Standard
 * https://github.com/nette/coding-standard
 */

declare(strict_types=1);


return function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator): void {
	$containerConfigurator->import(PRESET_DIR . '/php71.php');

	$parameters = $containerConfigurator->parameters();

	$parameters->set('skip', [
		// issue #260
		PhpCsFixer\Fixer\Operator\TernaryToNullCoalescingFixer::class => ['src/Dibi/HashMap.php'],
		SlevomatCodingStandard\Sniffs\ControlStructures\RequireNullCoalesceOperatorSniff::class => ['src/Dibi/HashMap.php'],
	]);
};
