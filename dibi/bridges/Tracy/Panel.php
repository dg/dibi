<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (http://davidgrudl.com)
 */

namespace Dibi\Bridges\Tracy;

use dibi,
	Tracy;


/**
 * Dibi panel for Tracy.
 *
 * @author     David Grudl
 */
class Panel extends \DibiObject implements Tracy\IBarPanel
{
	/** @var int maximum SQL length */
	static public $maxLength = 1000;

	/** @var bool  explain queries? */
	public $explain;

	/** @var int */
	public $filter;

	/** @var array */
	private $events = array();


	public function __construct($explain = TRUE, $filter = NULL)
	{
		$this->filter = $filter ? (int) $filter : \DibiEvent::QUERY;
		$this->explain = $explain;
	}


	public function register(\DibiConnection $connection)
	{
		Tracy\Debugger::getBar()->addPanel($this);
		Tracy\Debugger::getBlueScreen()->addPanel(array(__CLASS__, 'renderException'));
		$connection->onEvent[] = array($this, 'logEvent');
	}


	/**
	 * After event notification.
	 * @return void
	 */
	public function logEvent(\DibiEvent $event)
	{
		if (($event->type & $this->filter) === 0) {
			return;
		}
		$this->events[] = $event;
	}


	/**
	 * Returns blue-screen custom tab.
	 * @return mixed
	 */
	public static function renderException($e)
	{
		if ($e instanceof \DibiException && $e->getSql()) {
			return array(
				'tab' => 'SQL',
				'panel' => dibi::dump($e->getSql(), TRUE),
			);
		}
	}


	/**
	 * Returns HTML code for custom tab. (Tracy\IBarPanel)
	 * @return mixed
	 */
	public function getTab()
	{
		$totalTime = 0;
		$count = count($this->events);
		foreach ($this->events as $event) {
			$totalTime += $event->time;
		}
		return '<span title="dibi"><svg viewBox="0 0 2048 2048" style="vertical-align: bottom; width:1.23em; height:1.55em"><path fill="' . ( $count ? '#b079d6' : '#aaa') . '" d="M1024 896q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0 768q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-384q237 0 443-43t325-127v170q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-170q119 84 325 127t443 43zm0-1152q208 0 385 34.5t280 93.5 103 128v128q0 69-103 128t-280 93.5-385 34.5-385-34.5-280-93.5-103-128v-128q0-69 103-128t280-93.5 385-34.5z"/></svg><span class="tracy-label">'
			. $count . ' queries'
			. ($totalTime ? sprintf(' / %0.1f ms', $totalTime * 1000) : '')
			. '</span></span>';
	}


	/**
	 * Returns HTML code for custom panel. (Tracy\IBarPanel)
	 * @return mixed
	 */
	public function getPanel()
	{
		$totalTime = $s = NULL;
		$h = 'htmlSpecialChars';
		foreach ($this->events as $event) {
			$totalTime += $event->time;
			$explain = NULL; // EXPLAIN is called here to work SELECT FOUND_ROWS()
			if ($this->explain && $event->type === \DibiEvent::SELECT) {
				try {
					$backup = array($event->connection->onEvent, dibi::$numOfQueries, dibi::$totalTime);
					$event->connection->onEvent = NULL;
					$cmd = is_string($this->explain) ? $this->explain : ($event->connection->getConfig('driver') === 'oracle' ? 'EXPLAIN PLAN FOR' : 'EXPLAIN');
					$explain = dibi::dump($event->connection->nativeQuery("$cmd $event->sql"), TRUE);
				} catch (\DibiException $e) {}
				list($event->connection->onEvent, dibi::$numOfQueries, dibi::$totalTime) = $backup;
			}

			$s .= '<tr><td>' . sprintf('%0.3f', $event->time * 1000);
			if ($explain) {
				static $counter;
				$counter++;
				$s .= "<br /><a href='#tracy-debug-DibiProfiler-row-$counter' class='tracy-toggle tracy-collapsed' rel='#tracy-debug-DibiProfiler-row-$counter'>explain</a>";
			}

			$s .= '</td><td class="tracy-DibiProfiler-sql">' . dibi::dump(strlen($event->sql) > self::$maxLength ? substr($event->sql, 0, self::$maxLength) . '...' : $event->sql, TRUE);
			if ($explain) {
				$s .= "<div id='tracy-debug-DibiProfiler-row-$counter' class='tracy-collapsed'>{$explain}</div>";
			}
			if ($event->source) {
				$s .= Tracy\Helpers::editorLink($event->source[0], $event->source[1]);//->class('tracy-DibiProfiler-source');
			}

			$s .= "</td><td>{$event->count}</td><td>{$h($event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name'))}</td></tr>";
		}

		return empty($this->events) ? '' :
			'<style> #tracy-debug td.tracy-DibiProfiler-sql { background: white !important }
			#tracy-debug .tracy-DibiProfiler-source { color: #999 !important }
			#tracy-debug tracy-DibiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>
			<h1>Queries: ' . count($this->events) . ($totalTime === NULL ? '' : sprintf(', time: %0.3f ms', $totalTime * 1000)) . '</h1>
			<div class="tracy-inner tracy-DibiProfiler">
			<table>
				<tr><th>Time&nbsp;ms</th><th>SQL Statement</th><th>Rows</th><th>Connection</th></tr>' . $s . '
			</table>
			</div>';
	}

}
