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
 * dibi FirePHP logger.
 *
 * @author     David Grudl
 * @package    dibi
 */
class DibiFirePhpLogger extends DibiObject
{
	/** maximum number of rows */
	static public $maxQueries = 30;

	/** maximum SQL length */
	static public $maxLength = 1000;

	/** @var int */
	public $filter;

	/** @var int  Elapsed time for all queries */
	public $totalTime = 0;

	/** @var int  Number of all queries */
	public $numOfQueries = 0;

	/** @var array */
	private static $fireTable = array(array('Time', 'SQL Statement', 'Rows', 'Connection'));



	/**
	 * @return bool
	 */
	public static function isAvailable()
	{
		return isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
	}



	public function __construct($filter = NULL)
	{
		$this->filter = $filter ? (int) $filter : DibiEvent::QUERY;
	}



	/**
	 * After event notification.
	 * @return void
	 */
	public function logEvent(DibiEvent $event)
	{
		if (headers_sent() || ($event->type & $this->filter) === 0 || count(self::$fireTable) > self::$maxQueries) {
			return;
		}

		$this->totalTime += $event->time;
		$this->numOfQueries++;
		self::$fireTable[] = array(
			sprintf('%0.3f', $event->time * 1000),
			strlen($event->sql) > self::$maxLength ? substr($event->sql, 0, self::$maxLength) . '...' : $event->sql,
			$event->result instanceof Exception ? 'ERROR' : (string) $event->count,
			$event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name')
		);

		header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
		header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
		header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

		$payload = json_encode(array(
			array(
				'Type' => 'TABLE',
				'Label' => 'dibi profiler (' . $this->numOfQueries . ' SQL queries took ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms)',
			),
			self::$fireTable,
		));
		foreach (str_split($payload, 4990) as $num => $s) {
			$num++;
			header("X-Wf-dibi-1-1-d$num: |$s|\\"); // protocol-, structure-, plugin-, message-index
		}
		header("X-Wf-dibi-1-1-d$num: |$s|");
	}

}
