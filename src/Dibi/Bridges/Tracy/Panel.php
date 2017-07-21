<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Bridges\Tracy;

use Dibi;
use Dibi\Event;
use Dibi\Helpers;
use Tracy;


/**
 * Dibi panel for Tracy.
 */
class Panel implements Tracy\IBarPanel
{
	use Dibi\Strict;

	/** @var int maximum SQL length */
	public static $maxLength = 1000;

	/** @var bool  explain queries? */
	public $explain;

	/** @var int */
	public $filter;

	/** @var array */
	private $events = [];


	public function __construct($explain = true, $filter = null)
	{
		$this->filter = $filter ? (int) $filter : Event::QUERY;
		$this->explain = $explain;
	}


	public function register(Dibi\Connection $connection)
	{
		Tracy\Debugger::getBar()->addPanel($this);
		Tracy\Debugger::getBlueScreen()->addPanel([__CLASS__, 'renderException']);
		$connection->onEvent[] = [$this, 'logEvent'];
	}


	/**
	 * After event notification.
	 * @return void
	 */
	public function logEvent(Event $event)
	{
		if (($event->type & $this->filter) === 0) {
			return;
		}
		$this->events[] = $event;
	}


	/**
	 * Returns blue-screen custom tab.
	 * @return array|null
	 */
	public static function renderException($e)
	{
		if ($e instanceof Dibi\Exception && $e->getSql()) {
			return [
				'tab' => 'SQL',
				'panel' => Helpers::dump($e->getSql(), true),
			];
		}
	}


	/**
	 * Returns HTML code for custom tab. (Tracy\IBarPanel)
	 * @return string
	 */
	public function getTab()
	{
		$totalTime = 0;
		$count = count($this->events);
		foreach ($this->events as $event) {
			$totalTime += $event->time;
		}
		return '<span title="dibi"><svg viewBox="0 0 2048 2048" style="vertical-align: bottom; width:1.23em; height:1.55em"><path fill="' . ($count ? '#b079d6' : '#aaa') . '" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"/></svg><span class="tracy-label">'
			. $count . ' queries'
			. ($totalTime ? ' / ' . number_format($totalTime * 1000, 1, '.', ' ') . ' ms' : '')
			. '</span></span>';
	}


	/**
	 * Returns HTML code for custom panel. (Tracy\IBarPanel)
	 * @return string|null
	 */
	public function getPanel()
	{
		if (!$this->events) {
			return null;
		}

		$totalTime = $s = null;
		foreach ($this->events as $event) {
			$totalTime += $event->time;
			$connection = $event->connection;
			$explain = null; // EXPLAIN is called here to work SELECT FOUND_ROWS()
			if ($this->explain && $event->type === Event::SELECT) {
				try {
					$backup = [$connection->onEvent, \dibi::$numOfQueries, \dibi::$totalTime];
					$connection->onEvent = null;
					$cmd = is_string($this->explain) ? $this->explain : ($connection->getConfig('driver') === 'oracle' ? 'EXPLAIN PLAN FOR' : 'EXPLAIN');
					$explain = @Helpers::dump($connection->nativeQuery("$cmd $event->sql"), true);
				} catch (Dibi\Exception $e) {
				}
				list($connection->onEvent, \dibi::$numOfQueries, \dibi::$totalTime) = $backup;
			}

			$s .= '<tr><td>' . number_format($event->time * 1000, 3, '.', ' ');
			if ($explain) {
				static $counter;
				$counter++;
				$s .= "<br /><a href='#tracy-debug-DibiProfiler-row-$counter' class='tracy-toggle tracy-collapsed' rel='#tracy-debug-DibiProfiler-row-$counter'>explain</a>";
			}

			$s .= '</td><td class="tracy-DibiProfiler-sql">' . Helpers::dump(strlen($event->sql) > self::$maxLength ? substr($event->sql, 0, self::$maxLength) . '...' : $event->sql, true);
			if ($explain) {
				$s .= "<div id='tracy-debug-DibiProfiler-row-$counter' class='tracy-collapsed'>{$explain}</div>";
			}
			if ($event->source) {
				$s .= Tracy\Helpers::editorLink($event->source[0], $event->source[1]);//->class('tracy-DibiProfiler-source');
			}

			$s .= "</td><td>{$event->count}</td></tr>";
		}

		return '<style> #tracy-debug td.tracy-DibiProfiler-sql { background: white !important }
			#tracy-debug .tracy-DibiProfiler-source { color: #999 !important }
			#tracy-debug tracy-DibiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>
			<h1>Queries: ' . count($this->events)
				. ($totalTime === null ? '' : ', time: ' . number_format($totalTime * 1000, 1, '.', ' ') . ' ms') . ', '
				. htmlspecialchars($connection->getConfig('driver') . ($connection->getConfig('name') ? '/' . $connection->getConfig('name') : '')
				. ($connection->getConfig('host') ? ' @ ' . $connection->getConfig('host') : '')) . '</h1>
			<div class="tracy-inner tracy-DibiProfiler">
			<table>
				<tr><th>Time&nbsp;ms</th><th>SQL Statement</th><th>Rows</th></tr>' . $s . '
			</table>
			</div>';
	}
}
