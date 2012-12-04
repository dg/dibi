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
 * The dibi reflector for MySQL databases.
 *
 * @author     David Grudl
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
		$res = $this->driver->query("SHOW FULL TABLES");
		$tables = array();
		while ($row = $res->fetch(FALSE)) {
			$tables[] = array(
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			);
		}
		return $tables;
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
		$res = $this->driver->query("SHOW FULL COLUMNS FROM {$this->driver->escape($table, dibi::IDENTIFIER)}");
		$columns = array();
		while ($row = $res->fetch(TRUE)) {
			$type = explode('(', $row['Type']);
			$columns[] = array(
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
		return $columns;
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
		$res = $this->driver->query("SHOW INDEX FROM {$this->driver->escape($table, dibi::IDENTIFIER)}");
		$indexes = array();
		while ($row = $res->fetch(TRUE)) {
			$indexes[$row['Key_name']]['name'] = $row['Key_name'];
			$indexes[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$indexes[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}
		return array_values($indexes);
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		throw new DibiNotImplementedException;
	}

}
