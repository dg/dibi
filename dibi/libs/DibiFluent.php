<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi SQL builder via fluent interfaces. EXPERIMENTAL!
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiFluent extends DibiObject implements IDataSource
{
	/** @var array */
	public static $masks = array(
		'SELECT' => array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'GROUP BY',
			'HAVING', 'ORDER BY', 'LIMIT', 'OFFSET'),
		'UPDATE' => array('UPDATE', 'SET', 'WHERE', 'ORDER BY', 'LIMIT'),
		'INSERT' => array('INSERT', 'INTO', 'VALUES', 'SELECT'),
		'DELETE' => array('DELETE', 'FROM', 'USING', 'WHERE', 'ORDER BY', 'LIMIT'),
	);

	/** @var array  default modifiers for arrays */
	public static $modifiers = array(
		'SELECT' => '%n',
		'FROM' => '%n',
		'IN' => '%l',
		'VALUES' => '%l',
		'SET' => '%a',
		'WHERE' => '%and',
		'HAVING' => '%and',
		'ORDER BY' => '%by',
		'GROUP BY' => '%by',
	);

	/** @var array  clauses separators */
	public static $separators = array(
		'SELECT' => ',',
		'FROM' => FALSE,
		'WHERE' => 'AND',
		'GROUP BY' => ',',
		'HAVING' => 'AND',
		'ORDER BY' => ',',
		'LIMIT' => FALSE,
		'OFFSET' => FALSE,
		'SET' => ',',
		'VALUES' => ',',
		'INTO' => FALSE,
	);

	/** @var DibiConnection */
	private $connection;

	/** @var string */
	private $command;

	/** @var array */
	private $clauses = array();

	/** @var array */
	private $flags = array();

	/** @var array */
	private $cursor;



	/**
	 * @param  DibiConnection
	 */
	public function __construct(DibiConnection $connection)
	{
		$this->connection = $connection;
	}



	/**
	 * Appends new argument to the clause.
	 * @param  string clause name
	 * @param  array  arguments
	 * @return DibiFluent  provides a fluent interface
	 */
	public function __call($clause, $args)
	{
		$clause = self::_formatClause($clause);

		// lazy initialization
		if ($this->command === NULL) {
			if (isset(self::$masks[$clause])) {
				$this->clauses = array_fill_keys(self::$masks[$clause], NULL);
			}
			$this->cursor = & $this->clauses[$clause];
			$this->cursor = array();
			$this->command = $clause;
		}

		// special types or argument
		if (count($args) === 1) {
			$arg = $args[0];
			// TODO: really ignore TRUE?
			if ($arg === TRUE) { // flag
				$args = array();

			} elseif (is_string($arg) && preg_match('#^[a-z:_][a-z0-9_.:]*$#i', $arg)) { // identifier
				$args = array('%n', $arg);

			} elseif ($arg instanceof self) {
				$args = array_merge(array('('), $arg->_export(), array(')'));

			} elseif (is_array($arg) || $arg instanceof ArrayObject) { // any array
				if (isset(self::$modifiers[$clause])) {
					$args = array(self::$modifiers[$clause], $arg);

				} elseif (is_string(key($arg))) { // associative array
					$args = array('%a', $arg);
				}
			} // case $arg === FALSE is handled below
		}

		if (array_key_exists($clause, $this->clauses)) {
			// append to clause
			$this->cursor = & $this->clauses[$clause];

			// TODO: really delete?
			if ($args === array(FALSE)) {
				$this->cursor = NULL;
				return $this;
			}

			if (isset(self::$separators[$clause])) {
				$sep = self::$separators[$clause];
				if ($sep === FALSE) {
					$this->cursor = array();

				} elseif (!empty($this->cursor)) {
					$this->cursor[] = $sep;
				}
			}

		} else {
			// append to currect flow
			if ($args === array(FALSE)) {
				return $this;
			}

			$this->cursor[] = $clause;
		}

		if ($this->cursor === NULL) {
			$this->cursor = array();
		}

		array_splice($this->cursor, count($this->cursor), 0, $args);
		return $this;
	}



	/**
	 * Switch to a clause.
	 * @param  string clause name
	 * @return DibiFluent  provides a fluent interface
	 */
	public function clause($clause, $remove = FALSE)
	{
		$this->cursor = & $this->clauses[self::_formatClause($clause)];

		if ($remove) {
			$this->cursor = NULL;

		} elseif ($this->cursor === NULL) {
			$this->cursor = array();
		}

		return $this;
	}



	/**
	 * Change a SQL flag.
	 * @param  string  flag name
	 * @param  bool  value
	 * @return DibiFluent  provides a fluent interface
	 */
	public function setFlag($flag, $value = TRUE)
	{
		$flag = strtoupper($flag);
		if ($value) {
			$this->flags[$flag] = TRUE;
		} else {
			unset($this->flags[$flag]);
		}
		return $this;
	}



	/**
	 * Is a flag set?
	 * @param  string  flag name
	 * @return bool
	 */
	final public function getFlag($flag)
	{
		return isset($this->flags[strtoupper($flag)]);
	}



	/**
	 * Returns SQL command.
	 * @return string
	 */
	final public function getCommand()
	{
		return $this->command;
	}



	/**
	 * Returns the dibi connection.
	 * @return DibiConnection
	 */
	final public function getConnection()
	{
		return $this->connection;
	}



	/********************* executing ****************d*g**/



	/**
	 * Generates and executes SQL query.
	 * @param  mixed what to return?
	 * @return DibiResult|int  result set object (if any)
	 * @throws DibiException
	 */
	public function execute($return = NULL)
	{
		$res = $this->connection->query($this->_export());
		return $return === dibi::IDENTIFIER ? $this->connection->getInsertId() : $res;
	}



	/**
	 * Generates, executes SQL query and fetches the single row.
	 * @return DibiRow|FALSE  array on success, FALSE if no next record
	 */
	public function fetch()
	{
		if ($this->command === 'SELECT') {
			return $this->connection->query($this->_export(NULL, array('%lmt', 1)))->fetch();
		} else {
			return $this->connection->query($this->_export())->fetch();
		}
	}



	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, FALSE if no next record
	 */
	public function fetchSingle()
	{
		if ($this->command === 'SELECT') {
			return $this->connection->query($this->_export(NULL, array('%lmt', 1)))->fetchSingle();
		} else {
			return $this->connection->query($this->_export())->fetchSingle();
		}
	}



	/**
	 * Fetches all records from table.
	 * @param  int  offset
	 * @param  int  limit
	 * @return array
	 */
	public function fetchAll($offset = NULL, $limit = NULL)
	{
		return $this->connection->query($this->_export(NULL, array('%ofs %lmt', $offset, $limit)))->fetchAll();
	}



	/**
	 * Fetches all records from table and returns associative tree.
	 * @param  string  associative descriptor
	 * @return array
	 */
	public function fetchAssoc($assoc)
	{
		return $this->connection->query($this->_export())->fetchAssoc($assoc);
	}



	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 */
	public function fetchPairs($key = NULL, $value = NULL)
	{
		return $this->connection->query($this->_export())->fetchPairs($key, $value);
	}



	/**
	 * Required by the IteratorAggregate interface.
	 * @param  int  offset
	 * @param  int  limit
	 * @return DibiResultIterator
	 */
	public function getIterator($offset = NULL, $limit = NULL)
	{
		return $this->connection->query($this->_export(NULL, array('%ofs %lmt', $offset, $limit)))->getIterator();
	}



	/**
	 * Generates and prints SQL query or it's part.
	 * @param  string clause name
	 * @return bool
	 */
	public function test($clause = NULL)
	{
		return $this->connection->test($this->_export($clause));
	}



	/**
	 * @return int
	 */
	public function count()
	{
		return (int) $this->connection->query(
			'SELECT COUNT(*) FROM (%ex', $this->_export(), ') AS [data]'
		)->fetchSingle();
	}



	/********************* exporting ****************d*g**/



	/**
	 * @return DibiDataSource
	 */
	public function toDataSource()
	{
		return new DibiDataSource($this->connection->sql($this->_export()), $this->connection);
	}



	/**
	 * Returns SQL query.
	 * @return string
	 */
	final public function __toString()
	{
		return $this->connection->sql($this->_export());
	}



	/**
	 * Generates parameters for DibiTranslator.
	 * @param  string clause name
	 * @return array
	 */
	protected function _export($clause = NULL, $args = array())
	{
		if ($clause === NULL) {
			$data = $this->clauses;

		} else {
			$clause = self::_formatClause($clause);
			if (array_key_exists($clause, $this->clauses)) {
				$data = array($clause => $this->clauses[$clause]);
			} else {
				return array();
			}
		}

		foreach ($data as $clause => $statement) {
			if ($statement !== NULL) {
				$args[] = $clause;
				if ($clause === $this->command) {
					$args[] = implode(' ', array_keys($this->flags));
				}
				array_splice($args, count($args), 0, $statement);
			}
		}

		return $args;
	}



	/**
	 * Format camelCase clause name to UPPER CASE.
	 * @param  string
	 * @return string
	 */
	private static function _formatClause($s)
	{
		if ($s === 'order' || $s === 'group') {
			$s .= 'By';
			trigger_error("Did you mean '$s'?", E_USER_NOTICE);
		}
		return strtoupper(preg_replace('#[A-Z]#', ' $0', $s));

	}

}


// PHP < 5.2 compatibility
if (!function_exists('array_fill_keys')) {
	function array_fill_keys($keys, $value)
	{
		return array_combine($keys, array_fill(0, count($keys), $value));
	}
}
