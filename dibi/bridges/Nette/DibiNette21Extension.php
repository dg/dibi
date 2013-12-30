<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (http://davidgrudl.com)
 */


/**
 * Dibi extension for Nette Framework 2.1. Creates 'connection' service.
 *
 * @author     David Grudl
 * @package    dibi\nette
 * @phpversion 5.3
 */
class DibiNette21Extension extends Nette\DI\CompilerExtension
{

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();

		$useProfiler = isset($config['profiler'])
			? $config['profiler']
			: $container->parameters['debugMode'];

		unset($config['profiler']);

		if (isset($config['flags'])) {
			$flags = 0;
			foreach ((array) $config['flags'] as $flag) {
				$flags |= constant($flag);
			}
			$config['flags'] = $flags;
		}

		$connection = $container->addDefinition($this->prefix('connection'))
			->setClass('DibiConnection', array($config));

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setClass('DibiNettePanel')
				->addSetup('Nette\Diagnostics\Debugger::getBar()->addPanel(?)', array('@self'))
				->addSetup('Nette\Diagnostics\Debugger::getBlueScreen()->addPanel(?)', array('DibiNettePanel::renderException'));

			$connection->addSetup('$service->onEvent[] = ?', array(array($panel, 'logEvent')));
		}
	}

}
