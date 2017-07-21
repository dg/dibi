<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Bridges\Nette;

use Dibi;
use Nette;


/**
 * Dibi extension for Nette Framework 2.2. Creates 'connection' & 'panel' services.
 */
class DibiExtension22 extends Nette\DI\CompilerExtension
{
	/** @var bool */
	private $debugMode;


	public function __construct($debugMode = NULL)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();

		if ($this->debugMode === NULL) {
			$this->debugMode = $container->parameters['debugMode'];
		}

		$useProfiler = isset($config['profiler'])
			? $config['profiler']
			: class_exists('Tracy\Debugger') && $this->debugMode;

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

		if (class_exists('Tracy\Debugger')) {
			$connection->addSetup(
				[new Nette\DI\Statement('Tracy\Debugger::getBlueScreen'), 'addPanel'],
				[['Dibi\Bridges\Tracy\Panel', 'renderException']]
			);
		}
		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setClass('Dibi\Bridges\Tracy\Panel', [
					isset($config['explain']) ? $config['explain'] : TRUE,
					isset($config['filter']) && $config['filter'] === FALSE ? Dibi\Event::ALL : Dibi\Event::QUERY,
				]);
			$connection->addSetup([$panel, 'register'], [$connection]);
		}
	}
}
