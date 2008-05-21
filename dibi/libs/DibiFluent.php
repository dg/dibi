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
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * dibi SQL builder via fluent interfaces. EXPERIMENTAL!
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
final class DibiFluent extends /*Nette::*/Object
{
	/** @var array */
	public static $masks = array(
		'SELECT' => array('SELECT', 'DISTINCT', 'FROM', 'WHERE', 'GROUP BY',
			'HAVING', 'ORDER BY', 'LIMIT', 'OFFSET', '%end'),
		'UPDATE' => array('UPDATE', 'SET', 'WHERE', 'ORDER BY', 'LIMIT', '%end'),
		'INSERT' => array('INSERT', 'INTO', 'VALUES', 'SELECT', '%end'),
		'DELETE' => array('DELETE', 'FROM', 'USING', 'WHERE', 'ORDER BY', 'LIMIT', '%end'),
	);

	/** @var array */
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
		$clause = self::_clause($clause);

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
			if ($arg === TRUE) {
				$args = array();

			} elseif (is_string($arg) && preg_match('#^[a-z][a-z0-9_.]*$#i', $arg)) {
				$args = array('%n', $arg);
			}
		}

		if (array_key_exists($clause, $this->clauses)) {
			// append to clause
			$this->cursor = & $this->clauses[$clause];

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
		$this->cursor = & $this->clauses[self::_clause($clause)];

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
	final public function getFlag($flag, $value = TRUE)
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
	 * Generates and executes SQL query.
	 * @return DibiResult|NULL  result set object (if any)
	 * @throws DibiException
	 */
	public function execute()
	{
		return $this->connection->query($this->_export());
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
	 * Generates parameters for DibiTranslator.
	 * @param  string clause name
	 * @return array
	 */
	protected function _export($clause = NULL)
	{
		if ($clause === NULL) {
			$data = $this->clauses;

		} else {
			$clause = self::_clause($clause);
			if (array_key_exists($clause, $this->clauses)) {
				$data = array($clause => $this->clauses[$clause]);
			} else {
				return array();
			}
		}

		$args = array();
		foreach ($data as $clause => $statement) {
			if ($statement !== NULL) {
				if ($clause[0] !== '%') {
					$args[] = $clause;
					if ($clause === $this->command) {
						$args[] = implode(' ', array_keys($this->flags));
					}
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
	private static function _clause($s)
	{
		if ($s === 'order' || $s === 'group') {
			$s .= 'By';
			trigger_error("Did you mean '$s'?", E_USER_NOTICE);
		}
		return strtoupper(preg_replace('#[A-Z]#', ' $0', $s));

	}



	/**
	 * Returns (highlighted) SQL query.
	 * @return string
	 */
	final public function __toString()
	{
		ob_start();
		$this->test();
		return ob_get_clean();
	}

}


// PHP < 5.2 compatibility
if (!function_exists('array_fill_keys')) {
	function array_fill_keys($keys, $value)
	{
		return array_combine($keys, array_fill(0, count($keys), $value));
	}
}
