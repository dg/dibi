<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */



/**
 * Dibi extension for Nette Framework. Creates 'connection' service.
 *
 * @author     David Grudl
 * @package    dibi\nette
 * @phpversion 5.3
 */
class DibiNetteExtension extends Nette\Config\CompilerExtension
{

	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();

		$useProfiler = isset($config['profiler'])
			? $config['profiler']
			: !$container->parameters['productionMode'];

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
				->addSetup('Nette\Diagnostics\Debugger::$bar->addPanel(?)', array('@self'))
				->addSetup('Nette\Diagnostics\Debugger::$blueScreen->addPanel(?)', array(array('@self', 'renderException')));

			$connection->addSetup('$service->onEvent[] = ?', array(array($panel, 'logEvent')));
		}
	}

}
