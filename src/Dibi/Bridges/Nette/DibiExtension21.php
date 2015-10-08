<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Bridges\Nette;

use Nette;


/**
 * Dibi extension for Nette Framework 2.1. Creates 'connection' service.
 */
class DibiExtension21 extends Nette\DI\CompilerExtension
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
			->setClass('Dibi\Connection', [$config])
			->setAutowired(isset($config['autowired']) ? $config['autowired'] : TRUE);

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setClass('Dibi\Bridges\Nette\Panel')
				->addSetup('Nette\Diagnostics\Debugger::getBar()->addPanel(?)', ['@self'])
				->addSetup('Nette\Diagnostics\Debugger::getBlueScreen()->addPanel(?)', ['Dibi\Bridges\Nette\Panel::renderException']);

			$connection->addSetup('$service->onEvent[] = ?', [[$panel, 'logEvent']]);
		}
	}

}
