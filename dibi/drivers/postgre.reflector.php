<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 *
 * @package    dibi\drivers
 */


/**
 * The dibi reflector for PostgreSQL databases.
 *
 * @author     David Grudl
 * @package    dibi\drivers
 * @internal
 */
class DibiPostgreReflector extends DibiObject implements IDibiReflector
{
	/** @var IDibiDriver */
	private $driver;



	public function __construct(IDibiDriver $driver)
	{
		$this->driver = $driver;

		$version = pg_parameter_status($driver->getResource(), 'server_version'); // safer then the pg_version()
		if ($version < 8) {
			throw new DibiDriverException('Reflection requires PostgreSQL 8.');
		}
	}



	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		$res = $this->driver->query("
			SELECT table_name as name, CAST(table_type = 'VIEW' AS INTEGER) as view
			FROM information_schema.tables
			WHERE table_schema = current_schema()
		");
		$tables = pg_fetch_all($res->resultResource);
		return $tables ? $tables : array();
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$_table = $this->driver->escape($table, dibi::TEXT);
		$res = $this->driver->query("
			SELECT indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
			WHERE pg_class.relname = $_table
		");
		$primary = (int) pg_fetch_object($res->resultResource)->indkey;

		$res = $this->driver->query("
			SELECT *
			FROM information_schema.columns
			WHERE table_name = $_table AND table_schema = current_schema()
			ORDER BY ordinal_position
		");
		$columns = array();
		while ($row = $res->fetch(TRUE)) {
			$size = (int) max($row['character_maximum_length'], $row['numeric_precision']);
			$columns[] = array(
				'name' => $row['column_name'],
				'table' => $table,
				'nativetype' => strtoupper($row['udt_name']),
				'size' => $size ? $size : NULL,
				'nullable' => $row['is_nullable'] === 'YES',
				'default' => $row['column_default'],
				'autoincrement' => (int) $row['ordinal_position'] === $primary && substr($row['column_default'], 0, 7) === 'nextval',
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
		$_table = $this->driver->escape($table, dibi::TEXT);
		$res = $this->driver->query("
			SELECT ordinal_position, column_name
			FROM information_schema.columns
			WHERE table_name = $_table AND table_schema = current_schema()
			ORDER BY ordinal_position
		");

		$columns = array();
		while ($row = $res->fetch(TRUE)) {
			$columns[$row['ordinal_position']] = $row['column_name'];
		}

		$res = $this->driver->query("
			SELECT pg_class2.relname, indisunique, indisprimary, indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid
			INNER JOIN pg_class as pg_class2 on pg_class2.oid = pg_index.indexrelid
			WHERE pg_class.relname = $_table
		");

		$indexes = array();
		while ($row = $res->fetch(TRUE)) {
			$indexes[$row['relname']]['name'] = $row['relname'];
			$indexes[$row['relname']]['unique'] = $row['indisunique'] === 't';
			$indexes[$row['relname']]['primary'] = $row['indisprimary'] === 't';
			foreach (explode(' ', $row['indkey']) as $index) {
				$indexes[$row['relname']]['columns'][] = $columns[$index];
			}
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
