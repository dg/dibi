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
 * The dibi driver for PDO.
 *
 * Connection options:
 *   - 'dsn' - driver specific DSN
 *   - 'username' (or 'user')
 *   - 'password' (or 'pass')
 *   - 'options' - driver specific options array
 *   - 'pdo' - PDO object (optional)
 *   - 'lazy' - if TRUE, connection will be established only when required
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 */
class DibiPdoDriver extends /*Nette::*/Object implements IDibiDriver
{

	/**
	 * Connection resource.
	 * @var PDO
	 */
	private $connection;


	/**
	 * Resultset resource.
	 * @var PDOStatement
	 */
	private $resultSet;


	/**
	 * Affected rows.
	 * @var int|FALSE
	 */
	private $affectedRows = FALSE;



	/**
	 * @throws DibiException
	 */
	public function __construct()
	{
		if (!extension_loaded('pdo')) {
			throw new DibiDriverException("PHP extension 'pdo' is not loaded.");
		}
	}



	/**
	 * Connects to a database.
	 *
	 * @return void
	 * @throws DibiException
	 */
	public function connect(array &$config)
	{
		DibiConnection::alias($config, 'username', 'user');
		DibiConnection::alias($config, 'password', 'pass');
		DibiConnection::alias($config, 'dsn');
		DibiConnection::alias($config, 'pdo');
		DibiConnection::alias($config, 'options');

		if ($config['pdo'] instanceof PDO) {
			$this->connection = $config['pdo'];

		} else try {
			$this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);

		} catch (PDOException $e) {
			throw new DibiDriverException($e->getMessage(), $e->getCode());
		}

		if (!$this->connection) {
			throw new DibiDriverException('Connecting error.');
		}
	}



	/**
	 * Disconnects from a database.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		$this->connection = NULL;
	}



	/**
	 * Executes the SQL query.
	 *
	 * @param  string      SQL statement.
	 * @return IDibiDriver|NULL
	 * @throws DibiDriverException
	 */
	public function query($sql)
	{
		// must detect if SQL returns result set or num of affected rows
		$cmd = strtoupper(substr(ltrim($sql), 0, 6));
		$list = array('UPDATE'=>1, 'DELETE'=>1, 'INSERT'=>1, 'REPLAC'=>1);

		if (isset($list[$cmd])) {
			$this->resultSet = NULL;
			$this->affectedRows = $this->connection->exec($sql);

			if ($this->affectedRows === FALSE) {
				$this->throwException($sql);
			}

			return NULL;

		} else {
			$this->resultSet = $this->connection->query($sql);
			$this->affectedRows = FALSE;

			if ($this->resultSet === FALSE) {
				$this->throwException($sql);
			}

			return clone $this;
		}
	}



	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 *
	 * @return int|FALSE  number of rows or FALSE on error
	 */
	public function affectedRows()
	{
		return $this->affectedRows;
	}



	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 *
	 * @return int|FALSE  int on success or FALSE on failure
	 */
	public function insertId($sequence)
	{
		return $this->connection->lastInsertId();
	}



	/**
	 * Begins a transaction (if supported).
	 * @return void
	 * @throws DibiDriverException
	 */
	public function begin()
	{
		if (!$this->connection->beginTransaction()) {
			$this->throwException();
		}
	}



	/**
	 * Commits statements in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function commit()
	{
		if (!$this->connection->commit()) {
			$this->throwException();
		}
	}



	/**
	 * Rollback changes in a transaction.
	 * @return void
	 * @throws DibiDriverException
	 */
	public function rollback()
	{
		if (!$this->connection->rollBack()) {
			$this->throwException();
		}
	}



	/**
	 * Encodes data for use in an SQL statement.
	 *
	 * @param  string    value
	 * @param  string    type (dibi::FIELD_TEXT, dibi::FIELD_BOOL, ...)
	 * @return string    encoded value
	 * @throws InvalidArgumentException
	 */
	public function escape($value, $type)
	{
		switch ($type) {
		case dibi::FIELD_TEXT:
			return $this->connection->quote($value, PDO::PARAM_STR);

		case dibi::FIELD_BINARY:
			return $this->connection->quote($value, PDO::PARAM_LOB);

		case dibi::IDENTIFIER:
			switch ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			case 'mysql':
				return '`' . str_replace('.', '`.`', $value) . '`';

			case 'pgsql':
				$a = strrpos($value, '.');
				if ($a === FALSE) {
					return '"' . str_replace('"', '""', $value) . '"';
				} else {
					return substr($value, 0, $a) . '."' . str_replace('"', '""', substr($value, $a + 1)) . '"';
				}

			case 'sqlite':
			case 'sqlite2':
			case 'odbc':
			case 'oci': // TODO: not tested
			case 'mssql':
				return '[' . str_replace('.', '].[', $value) . ']';

			default:
				return $value;
			}

		case dibi::FIELD_BOOL:
			return $this->connection->quote($value, PDO::PARAM_BOOL);

		case dibi::FIELD_DATE:
			return date("'Y-m-d'", $value);

		case dibi::FIELD_DATETIME:
			return date("'Y-m-d H:i:s'", $value);

		default:
			throw new InvalidArgumentException('Unsupported type.');
		}
	}



	/**
	 * Decodes data from result set.
	 *
	 * @param  string    value
	 * @param  string    type (dibi::FIELD_BINARY)
	 * @return string    decoded value
	 * @throws InvalidArgumentException
	 */
	public function unescape($value, $type)
	{
		throw new InvalidArgumentException('Unsupported type.');
	}



	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 *
	 * @param  string &$sql  The SQL query that will be modified.
	 * @param  int $limit
	 * @param  int $offset
	 * @return void
	 */
	public function applyLimit(&$sql, $limit, $offset)
	{
		throw new NotSupportedException('PDO does not support applying limit or offset.');
	}



	/**
	 * Returns the number of rows in a result set.
	 *
	 * @return int
	 */
	public function rowCount()
	{
		throw new DibiDriverException('Row count is not available for unbuffered queries.');
	}



	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * internal usage only
	 *
	 * @param  bool     TRUE for associative array, FALSE for numeric
	 * @return array    array on success, nonarray if no next record
	 */
	public function fetch($type)
	{
		return $this->resultSet->fetch($type ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
	}



	/**
	 * Moves cursor position without fetching row.
	 *
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	public function seek($row)
	{
		throw new DibiDriverException('Cannot seek an unbuffered result set.');
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function free()
	{
		$this->resultSet = NULL;
	}



	/**
	 * Returns metadata for all columns in a result set.
	 *
	 * @return array
	 * @throws DibiException
	 */
	public function getColumnsMeta()
	{
		$count = $this->resultSet->columnCount();
		$meta = array();
		for ($i = 0; $i < $count; $i++) {
			// items 'name' and 'table' are required
			$info = @$this->resultSet->getColumnsMeta($i); // intentionally @
			if ($info === FALSE) {
				throw new DibiDriverException('Driver does not support meta data.');
			}
			$meta[] = $info;
		}
		return $meta;
	}



	/**
	 * Converts database error to DibiDriverException.
	 *
	 * @throws DibiDriverException
	 */
	protected function throwException($sql = NULL)
	{
		$err = $this->connection->errorInfo();
		throw new DibiDriverException("SQLSTATE[$err[0]]: $err[2]", $err[1], $sql);
	}



	/**
	 * Returns the connection resource.
	 *
	 * @return PDO
	 */
	public function getResource()
	{
		return $this->connection;
	}



	/**
	 * Returns the result set resource.
	 *
	 * @return PDOStatement
	 */
	public function getResultResource()
	{
		return $this->resultSet;
	}



	/**
	 * Gets a information of the current database.
	 *
	 * @return DibiReflection
	 */
	function getDibiReflection()
	{}

}
