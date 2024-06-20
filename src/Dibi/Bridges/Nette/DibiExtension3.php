<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Bridges\Nette;

use Dibi;
use Nette;
use Nette\Schema\Expect;
use Tracy;


/**
 * Dibi extension for Nette Framework 3. Creates 'connection' & 'panel' services.
 */
class DibiExtension3 extends Nette\DI\CompilerExtension
{
	private ?bool $debugMode;
	private ?bool $cliMode;


	public function __construct(?bool $debugMode = null, ?bool $cliMode = null)
	{
		$this->debugMode = $debugMode;
		$this->cliMode = $cliMode;
	}


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'autowired' => Expect::bool(true),
			'flags' => Expect::anyOf(Expect::arrayOf('string'), Expect::type('dynamic')),
			'profiler' => Expect::bool(),
			'explain' => Expect::bool(true),
			'filter' => Expect::bool(true),
			'driver' => Expect::string()->dynamic(),
			'name' => Expect::string()->dynamic(),
			'lazy' => Expect::bool(false)->dynamic(),
			'onConnect' => Expect::array()->dynamic(),
			'substitutes' => Expect::arrayOf('string')->dynamic(),
			'result' => Expect::structure([
				'normalize' => Expect::bool(true),
				'formatDateTime' => Expect::string(),
				'formatTimeInterval' => Expect::string(),
				'formatJson' => Expect::string(),
			])->castTo('array'),
		])->otherItems(Expect::type('mixed'))
			->castTo('array');
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig();
		$this->debugMode ??= $container->parameters['debugMode'];
		$this->cliMode ??= $container->parameters['consoleMode'];

		$useProfiler = $config['profiler'] ?? (class_exists(Tracy\Debugger::class) && $this->debugMode && !$this->cliMode);
		unset($config['profiler']);

		if (is_array($config['flags'])) {
			$flags = 0;
			foreach ((array) $config['flags'] as $flag) {
				$flags |= constant($flag);
			}
			$config['flags'] = $flags;
		}

		$connection = $container->addDefinition($this->prefix('connection'))
			->setCreator(Dibi\Connection::class, [$config])
			->setAutowired($config['autowired']);

		if (class_exists(Tracy\Debugger::class)) {
			$connection->addSetup(
				[new Nette\DI\Definitions\Statement('Tracy\Debugger::getBlueScreen'), 'addPanel'],
				[[Dibi\Bridges\Tracy\Panel::class, 'renderException']],
			);
		}

		if ($useProfiler) {
			$panel = $container->addDefinition($this->prefix('panel'))
				->setCreator(Dibi\Bridges\Tracy\Panel::class, [
					$config['explain'],
					$config['filter'] ? Dibi\Event::QUERY : Dibi\Event::ALL,
				]);
			$connection->addSetup([$panel, 'register'], [$connection]);
		}
	}
}
