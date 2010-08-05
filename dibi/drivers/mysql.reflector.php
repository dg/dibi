<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi\drivers
 */


/**
 * The dibi reflector for MySQL databases.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi\drivers
 * @internal
 */
class DibiMySqlReflector extends DibiObject implements IDibiReflector
{
	/** @var IDibiDriver */
	private $driver;



	public function __construct(IDibiDriver $driver)
	{
		$this->driver = $driver;
	}



	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		/*$this->query("
			SELECT TABLE_NAME as name, TABLE_TYPE = 'VIEW' as view
			FROM INFORMATION_SCHEMA.TABLES
			WHERE TABLE_SCHEMA = DATABASE()
		");*/
		$this->driver->query("SHOW FULL TABLES");
		$res = array();
		while ($row = $this->driver->fetch(FALSE)) {
			$res[] = array(
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			);
		}
		$this->driver->free();
		return $res;
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		/*$table = $this->escape($table, dibi::TEXT);
		$this->query("
			SELECT *
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = $table AND TABLE_SCHEMA = DATABASE()
		");*/
		$this->driver->query("SHOW FULL COLUMNS FROM `$table`");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$type = explode('(', $row['Type']);
			$res[] = array(
				'name' => $row['Field'],
				'table' => $table,
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : NULL,
				'unsigned' => (bool) strstr($row['Type'], 'unsigned'),
				'nullable' => $row['Null'] === 'YES',
				'default' => $row['Default'],
				'autoincrement' => $row['Extra'] === 'auto_increment',
				'vendor' => $row,
			);
		}
		$this->driver->free();
		return $res;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		/*$table = $this->escape($table, dibi::TEXT);
		$this->query("
			SELECT *
			FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE TABLE_NAME = $table AND TABLE_SCHEMA = DATABASE()
			AND REFERENCED_COLUMN_NAME IS NULL
		");*/
		$this->driver->query("SHOW INDEX FROM `$table`");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$res[$row['Key_name']]['name'] = $row['Key_name'];
			$res[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$res[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$res[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		$this->driver->free();
		return array_values($res);
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		throw new NotImplementedException;
	}

}
