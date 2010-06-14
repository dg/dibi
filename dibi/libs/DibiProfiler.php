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
 * Profiler options:
 *   - 'explain' - explain SELECT queries?
 *   - 'filter' - which queries to log?
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 */
class DibiProfiler extends DibiObject implements IDibiProfiler, /*Nette\*/IDebugPanel
{
	/** maximum number of rows */
	static public $maxQueries = 30;

	/** maximum SQL length */
	static public $maxLength = 1000;

	/** @var string  Name of the file where SQL errors should be logged */
	private $file;

	/** @var bool  log to firebug? */
	public $useFirebug;

	/** @var bool  explain queries? */
	public $explainQuery = TRUE;

	/** @var int */
	private $filter = self::ALL;

	/** @var array */
	public static $tickets = array();

	/** @var array */
	public static $fireTable = array(array('Time', 'SQL Statement', 'Rows', 'Connection'));



	public function __construct(array $config)
	{
		if (class_exists(/*Nette\*/'Debug', FALSE) && is_callable(/*Nette\*/'Debug::addPanel')) {
			/*Nette\*/Debug::addPanel($this);
		}

		$this->useFirebug = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');

		if (isset($config['filter'])) {
			$this->setFilter($config['filter']);
		}

		if (isset($config['explain'])) {
			$this->explainQuery = (bool) $config['explain'];
		}
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
		if ($event & self::QUERY) dibi::$numOfQueries++;
		dibi::$elapsedTime = FALSE;
		self::$tickets[] = array($connection, $event, trim($sql), -microtime(TRUE), NULL, NULL);
		end(self::$tickets);
		return key(self::$tickets);
	}



	/**
	 * After event notification.
	 * @param  int
	 * @param  DibiResult
	 * @return void
	 */
	public function after($ticket, $res = NULL)
	{
		if (!isset(self::$tickets[$ticket])) {
			throw new InvalidArgumentException('Bad ticket number.');
		}

		$ticket = & self::$tickets[$ticket];
		$ticket[3] += microtime(TRUE);
		list($connection, $event, $sql, $time) = $ticket;

		dibi::$elapsedTime = $time;
		dibi::$totalTime += $time;

		if (($event & $this->filter) === 0) return;

		if ($event & self::QUERY) {
			try {
				$ticket[4] = $count = $res instanceof DibiResult ? count($res) : '-';
			} catch (Exception $e) {
				$count = '?';
			}

			if (count(self::$fireTable) < self::$maxQueries) {
				self::$fireTable[] = array(
					sprintf('%0.3f', $time * 1000),
					strlen($sql) > self::$maxLength ? substr($sql, 0, self::$maxLength) . '...' : $sql,
					$count,
					$connection->getConfig('driver') . '/' . $connection->getConfig('name')
				);

				if ($this->explainQuery && $event === self::SELECT) {
					$tmpSql = dibi::$sql;
					try {
						$ticket[5] = dibi::dump($connection->setProfiler(NULL)->nativeQuery('EXPLAIN ' . $sql), TRUE);
					} catch (DibiException $e) {}
					$connection->setProfiler($this);
					dibi::$sql = $tmpSql;
				}

				if ($this->useFirebug && !headers_sent()) {
					header('X-Wf-Protocol-dibi: http://meta.wildfirehq.org/Protocol/JsonStream/0.2');
					header('X-Wf-dibi-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0');
					header('X-Wf-dibi-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1');

					$payload = json_encode(array(
						array(
							'Type' => 'TABLE',
							'Label' => 'dibi profiler (' . dibi::$numOfQueries . ' SQL queries took ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms)',
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

			if ($this->file) {
				$this->writeFile(
					"OK: " . $sql
					. ($res instanceof DibiResult ? ";\n-- rows: " . $count : '')
					. "\n-- takes: " . sprintf('%0.3f', $time * 1000) . ' ms'
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



	/********************* interface Nette\IDebugPanel ****************d*g**/



	/**
	 * Returns HTML code for custom tab.
	 * @return mixed
	 */
	public function getTab()
	{
		return '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC">'
			. dibi::$numOfQueries . ' queries';
	}



	/**
	 * Returns HTML code for custom panel.
	 * @return mixed
	 */
	public function getPanel()
	{
		if (!dibi::$numOfQueries) return;

		$content = "
<h1>Queries: " . dibi::$numOfQueries . (dibi::$totalTime === NULL ? '' : ', time: ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms') . "</h1>

<style>
	#nette-debug-DibiProfiler td.dibi-sql { background: white }
	#nette-debug-DibiProfiler .nette-alt td.dibi-sql { background: #F5F5F5 }
	#nette-debug-DibiProfiler .dibi-sql div { display: none; margin-top: 10px; max-height: 150px; overflow:auto }
</style>

<div class='nette-inner'>
<table>
<tr>
	<th>Time</th><th>SQL Statement</th><th>Rows</th><th>Connection</th>
</tr>
";
		$i = 0; $classes = array('class="nette-alt"', '');
		foreach (self::$tickets as $ticket) {
			list($connection, $event, $sql, $time, $count, $explain) = $ticket;
			if (!($event & self::QUERY)) continue;
			$content .= "
<tr {$classes[++$i%2]}>
	<td>" . sprintf('%0.3f', $time * 1000) . ($explain ? "
	<br><a href='#' class='nette-toggler' rel='#nette-debug-DibiProfiler-row-$i'>explain&nbsp;&#x25ba;</a>" : '') . "</td>
	<td class='dibi-sql'>" . dibi::dump(strlen($sql) > self::$maxLength ? substr($sql, 0, self::$maxLength) . '...' : $sql, TRUE) . ($explain ? "
	<div id='nette-debug-DibiProfiler-row-$i'>{$explain}</div>" : '') . "</td>
	<td>{$count}</td>
	<td>" . htmlSpecialChars($connection->getConfig('driver') . '/' . $connection->getConfig('name')) . "</td>
</tr>
";
		}
		$content .= '</table></div>';
		return $content;
	}



	/**
	 * Returns panel ID.
	 * @return string
	 */
	public function getId()
	{
		return get_class($this);
	}

}
