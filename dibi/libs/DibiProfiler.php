<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi basic logger & profiler (experimental).
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 */
class DibiProfiler extends DibiObject implements IDibiProfiler
{
	/** maximum number of rows */
	const FIREBUG_MAX_ROWS = 30;

	/** maximum SQL length */
	const FIREBUG_MAX_LENGTH = 500;

	/** @var string  Name of the file where SQL errors should be logged */
	private $file;

	/** @var bool  log to firebug? */
	public $useFirebug;

	/** @var int */
	private $filter = self::ALL;

	/** @var array */
	public $tickets = array();

	/** @var array */
	public static $table = array(array('Time', 'SQL Statement', 'Rows', 'Connection'));



	public function __construct()
	{
		$this->useFirebug = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');
	}



	/**
	 * @param  string  filename
	 * @return DibiProfiler  provides a fluent interface
	 */
	public function setFile($file)
	{
		$this->file = $file;
		return $this;
	}



	/**
	 * @param  int
	 * @return DibiProfiler  provides a fluent interface
	 */
	public function setFilter($filter)
	{
		$this->filter = (int) $filter;
		return $this;
	}



	/**
	 * Before event notification.
	 * @param  DibiConnection
	 * @param  int     event name
	 * @param  string  sql
	 * @return int
	 */
	public function before(DibiConnection $connection, $event, $sql = NULL)
	{
		$this->tickets[] = array($connection, $event, $sql);
		end($this->tickets);
		return key($this->tickets);
	}



	/**
	 * After event notification.
	 * @param  int
	 * @param  DibiResult
	 * @return void
	 */
	public function after($ticket, $res = NULL)
	{
		if (!isset($this->tickets[$ticket])) {
			throw new InvalidArgumentException('Bad ticket number.');
		}

		list($connection, $event, $sql) = $this->tickets[$ticket];
		$sql = trim($sql);

		if (($event & $this->filter) === 0) return;

		if ($event & self::QUERY) {
			try {
				$count = $res instanceof DibiResult ? count($res) : '-';
			} catch (Exception $e) {
				$count = '?';
			}

			if ($this->useFirebug && !headers_sent()) {
				if (count(self::$table) < self::FIREBUG_MAX_ROWS) {
					self::$table[] = array(
						sprintf('%0.3f', dibi::$elapsedTime * 1000),
						strlen($sql) > self::FIREBUG_MAX_LENGTH ? substr($sql, 0, self::FIREBUG_MAX_LENGTH) . '...' : $sql,
						$count,
						$connection->getConfig('driver') . '/' . $connection->getConfig('name')
					);
				}

				header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
				header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
				header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

				$payload = array(
					array(
						'Type' => 'TABLE',
						'Label' => 'dibi profiler (' . dibi::$numOfQueries . ' SQL queries took ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms)',
					),
					self::$table,
				);
				$payload = json_encode($payload);
				foreach (str_split($payload, 4990) as $num => $s) {
					$num++;
					header("X-Wf-dibi-1-1-d$num: |$s|\\"); // protocol-, structure-, plugin-, message-index
				}
				header("X-Wf-dibi-1-1-d$num: |$s|");
			}

			if ($this->file) {
				$this->writeFile(
					"OK: " . $sql
					. ($res instanceof DibiResult ? ";\n-- rows: " . $count : '')
					. "\n-- takes: " . sprintf('%0.3f', dibi::$elapsedTime * 1000) . ' ms'
					. "\n-- driver: " . $connection->getConfig('driver') . '/' . $connection->getConfig('name')
					. "\n-- " . date('Y-m-d H:i:s')
					. "\n\n"
				);
			}
		}
	}



	/**
	 * After exception notification.
	 * @param  DibiDriverException
	 * @return void
	 */
	public function exception(DibiDriverException $exception)
	{
		if ((self::EXCEPTION & $this->filter) === 0) return;

		if ($this->useFirebug) {
			// TODO: implement
		}

		if ($this->file) {
			$message = $exception->getMessage();
			$code = $exception->getCode();
			if ($code) {
				$message = "[$code] $message";
			}
			$this->writeFile(
				"ERROR: $message"
				. "\n-- SQL: " . dibi::$sql
				. "\n-- driver: " //. $connection->getConfig('driver')
				. ";\n-- " . date('Y-m-d H:i:s')
				. "\n\n"
			);
		}
	}



	private function writeFile($message)
	{
		$handle = fopen($this->file, 'a');
		if (!$handle) return; // or throw exception?
		flock($handle, LOCK_EX);
		fwrite($handle, $message);
		fclose($handle);
	}

}
