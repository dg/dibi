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
class DibiProfiler extends DibiObject implements IDibiProfiler, /*Nette\*/IDebugPanel
{
	/** maximum number of rows */
	const MAX_ROWS = 30;

	/** maximum SQL length */
	const MAX_LENGTH = 500;

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
		if (class_exists(/*Nette\*/'Debug', FALSE) && is_callable('Debug::addPanel')) {
			/*Nette\*/Debug::addPanel($this);
		}

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

			if (count(self::$table) < self::MAX_ROWS) {
				self::$table[] = array(
					sprintf('%0.3f', dibi::$elapsedTime * 1000),
					strlen($sql) > self::MAX_LENGTH ? substr($sql, 0, self::MAX_LENGTH) . '...' : $sql,
					$count,
					$connection->getConfig('driver') . '/' . $connection->getConfig('name')
				);
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
					self::$table,
				));
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

		$content = '<h1>SQL queries: ' . dibi::$numOfQueries . (dibi::$totalTime === NULL ? '' : ', elapsed time: ' . sprintf('%0.3f', dibi::$totalTime * 1000) . ' ms') . '</h1>';
		if (self::$table) {
			$content .= '<table>';
			foreach (self::$table as $i => $row) {
				if ($i === 0) {
					$content .= "<tr><th>$row[0]</th><th>$row[1]</th><th>$row[2]</th><th>$row[3]</th></tr>";
				} else {
					$content .= "<tr ".($i%2 ? 'class="nette-alt"':'')."><td>$row[0]</td>	<td>$row[1]</td><td>$row[2]</td><td>$row[3]</td></tr>";
				}
			}
			$content .= '</table>';
		} else {
			$content .= '<p>no query</p>';
		}
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
