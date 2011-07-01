<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 *
 * @package    dibi
 */



/**
 * dibi basic logger & profiler (experimental).
 *
 * Profiler options:
 *   - 'explain' - explain SELECT queries?
 *   - 'filter' - which queries to log?
 *
 * @author     David Grudl
 */
class DibiProfiler extends DibiObject implements IDibiProfiler, IBarPanel
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
		if (is_callable('Nette\Diagnostics\Debugger::enable')) {
			eval('$tmp = Nette\Diagnostics\Debugger::$bar;');
			$tmp->addPanel($this);
			eval('$tmp = Nette\Diagnostics\Debugger::$blueScreen;');
			$tmp->addPanel(array($this, 'renderException'), __CLASS__);
		} elseif (is_callable('NDebugger::enable')) {
			NDebugger::$bar->addPanel($this);
			NDebugger::$blueScreen->addPanel(array($this, 'renderException'), __CLASS__);
		} elseif (is_callable('Debugger::enable')) {
			Debugger::$bar->addPanel($this);
			Debugger::$blueScreen->addPanel(array($this, 'renderException'), __CLASS__);
		}

		$this->useFirebug = isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'FirePHP/');

		if (isset($config['file'])) {
			$this->setFile($config['file']);
		}

		if (isset($config['filter'])) {
			$this->setFilter($config['filter']);
		}

		if (isset($config['explain'])) {
			$this->explainQuery = (bool) $config['explain'];
		}
	}



	public function renderException($e)
	{
		if ($e instanceof DibiException && $e->getSql()) {
			return array(
				'tab' => 'SQL',
				'panel' => dibi::dump($e->getSql(), TRUE),
			);
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
		$rc = new ReflectionClass('dibi');
		$dibiDir = dirname($rc->getFileName()) . DIRECTORY_SEPARATOR;
		$source = NULL;
		foreach (debug_backtrace(FALSE) as $row) {
			if (isset($row['file']) && is_file($row['file']) && strpos($row['file'], $dibiDir) !== 0) {
				$source = array($row['file'], (int) $row['line']);
				break;
			}
		}
		if ($event & self::QUERY) dibi::$numOfQueries++;
		dibi::$elapsedTime = FALSE;
		self::$tickets[] = array($connection, $event, trim($sql), -microtime(TRUE), NULL, $source);
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
		list($connection, $event, $sql, $time, , $source) = $ticket;

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
					. "\n-- source: " . implode(':', $source)
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



	/********************* interface Nette\Diagnostics\IBarPanel ****************d*g**/



	/**
	 * Returns HTML code for custom tab.
	 * @return mixed
	 */
	public function getTab()
	{
		return '<span title="dibi profiler"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAQAAAC1+jfqAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAAEYSURBVBgZBcHPio5hGAfg6/2+R980k6wmJgsJ5U/ZOAqbSc2GnXOwUg7BESgLUeIQ1GSjLFnMwsKGGg1qxJRmPM97/1zXFAAAAEADdlfZzr26miup2svnelq7d2aYgt3rebl585wN6+K3I1/9fJe7O/uIePP2SypJkiRJ0vMhr55FLCA3zgIAOK9uQ4MS361ZOSX+OrTvkgINSjS/HIvhjxNNFGgQsbSmabohKDNoUGLohsls6BaiQIMSs2FYmnXdUsygQYmumy3Nhi6igwalDEOJEjPKP7CA2aFNK8Bkyy3fdNCg7r9/fW3jgpVJbDmy5+PB2IYp4MXFelQ7izPrhkPHB+P5/PjhD5gCgCenx+VR/dODEwD+A3T7nqbxwf1HAAAAAElFTkSuQmCC" />'
			. dibi::$numOfQueries . ' queries</span>';
	}



	/**
	 * Returns HTML code for custom panel.
	 * @return mixed
	 */
	public function getPanel()
	{
		$s = NULL;
		$h = 'htmlSpecialChars';
		foreach (self::$tickets as $i => $ticket) {
			list($connection, $event, $sql, $time, $count, $source) = $ticket;
			if (!($event & self::QUERY)) continue;

			$explain = NULL; // EXPLAIN is called here to work SELECT FOUND_ROWS()
			if ($this->explainQuery && $event === self::SELECT) {
				try {
					$explain = dibi::dump($connection->setProfiler(NULL)->nativeQuery('EXPLAIN ' . $sql), TRUE);
				} catch (DibiException $e) {}
				$connection->setProfiler($this);
			}

			$s .= '<tr><td>' . sprintf('%0.3f', $time * 1000);
			if ($explain) {
				$s .= "<br /><a href='#' class='nette-toggler' rel='#nette-debug-DibiProfiler-row-$i'>explain&nbsp;&#x25ba;</a>";
			}

			$s .= '</td><td class="nette-DibiProfiler-sql">' . dibi::dump(strlen($sql) > self::$maxLength ? substr($sql, 0, self::$maxLength) . '...' : $sql, TRUE);
			if ($explain) {
				$s .= "<div id='nette-debug-DibiProfiler-row-$i' class='nette-collapsed'>{$explain}</div>";
			}
			if ($source) {
				list($file, $line) = $source;
				$s .= "<span class='nette-DibiProfiler-source' title='{$h($file)}:$line'>{$h(basename(dirname($file)) . '/' . basename($file))}:$line</span>";
			}

			$s .= "</td><td>{$count}</td><td>{$h($connection->getConfig('driver') . '/' . $connection->getConfig('name'))}</td></tr>";
		}

		return $s === NULL ? '' :
			'<style> #nette-debug td.nette-DibiProfiler-sql { background: white !important }
			#nette-debug .nette-DibiProfiler-source { color: #999 !important }
			#nette-debug nette-DibiProfiler tr table { margin: 8px 0; max-height: 150px; overflow:auto } </style>
			<h1>Queries: ' . dibi::$numOfQueries . (dibi::$totalTime === NULL ? '' : ', time: ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms') . '</h1>
			<div class="nette-inner nette-DibiProfiler">
			<table>
				<tr><th>Time&nbsp;ms</th><th>SQL Statement</th><th>Rows</th><th>Connection</th></tr>' . $s . '
			</table>
			</div>';
	}

}
