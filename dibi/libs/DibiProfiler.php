<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 * @version    $Id$
 */



/**
 * dibi basic logger & profiler (experimental).
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiProfiler extends DibiObject implements IDibiProfiler
{
	/** @var string  Name of the file where SQL errors should be logged */
	private $file;

	/** @var bool  log to firebug? */
	private $useFirebug;

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
	 * @return void
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}



	/**
	 * @param  int
	 * @return void
	 */
	public function setFilter($filter)
	{
		$this->filter = (int) $filter;
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

		if (($event & $this->filter) === 0) return;

		if ($event & self::QUERY) {
			if ($this->useFirebug) {
				self::$table[] = array(
					sprintf('%0.3f', dibi::$elapsedTime * 1000),
					trim(preg_replace('#\s+#', ' ', $sql)),
					$res instanceof DibiResult ? count($res) : '-',
					$connection->getConfig('driver') . '/' . $connection->getConfig('name')
				);
				$caption = 'dibi profiler (' . dibi::$numOfQueries . ' SQL queries took ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms)';

				$payload['FirePHP.Firebug.Console'][] = array('TABLE', array($caption, self::$table));
				$payload = json_encode($payload);
				foreach (str_split($payload, 4998) as $num => $s) {
					header('X-FirePHP-Data-' . str_pad(++$num, 12, '0', STR_PAD_LEFT) . ': ' . $s);
				}
			}

			if ($this->file) {
				$this->writeFile(
					"OK: " . $sql
					. ($res instanceof DibiResult ? ";\n-- rows: " . count($res) : '')
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
