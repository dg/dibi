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
 * The dibi reflector for MsSQL databases.
 *
 * @author     Steven Bredenberg
 * @package    dibi\drivers
 * @internal
 */
class DibiMsSqlReflector extends DibiObject implements IDibiReflector
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
		$res = $this->driver->query("
			SELECT TABLE_NAME, TABLE_TYPE
			FROM INFORMATION_SCHEMA.TABLES
		");
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
	 * Returns count of rows in a table
	 * @param  string
	 * @return integer
	 */
	public function getTableCount($table, $fallback=true)
	{
		if (empty($table)) {
			return false;
		}
		$result = $this->driver->query("
			SELECT MAX(rowcnt)
			FROM sys.sysindexes
			WHERE id=OBJECT_ID({$this->driver->escape($table, dibi::IDENTIFIER)})
		");
		$row = $result->fetch(FALSE);

		if (!is_array($row) || count($row) < 1) {
			if ($fallback) {
				$row = $this->driver->query("SELECT COUNT(*) FROM {$this->driver->escape($table, dibi::IDENTIFIER)}")->fetch(FALSE);
				$count = intval($row[0]);
			} else {
				$count = false;
			}
		} else {
			$count = intval($row[0]);
		}

		return $count;
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$res = $this->driver->query("
			SELECT * FROM
			INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_NAME = {$this->driver->escape($table, dibi::TEXT)}
			ORDER BY TABLE_NAME, ORDINAL_POSITION
		");
		$columns = array();
		while ($row = $res->fetch(TRUE)) {
			$size = false;
			$type = strtoupper($row['DATA_TYPE']);

			$size_cols = array(
				'DATETIME'=>'DATETIME_PRECISION',
				'DECIMAL'=>'NUMERIC_PRECISION',
				'CHAR'=>'CHARACTER_MAXIMUM_LENGTH',
				'NCHAR'=>'CHARACTER_OCTET_LENGTH',
				'NVARCHAR'=>'CHARACTER_OCTET_LENGTH',
				'VARCHAR'=>'CHARACTER_OCTET_LENGTH'
			);

			if (isset($size_cols[$type])) {
				if ($size_cols[$type]) {
					$size = $row[$size_cols[$type]];
				}
			}

			$columns[] = array(
				'name' => $row['COLUMN_NAME'],
				'table' => $table,
				'nativetype' => $type,
				'size' => $size,
				'unsigned' => NULL,
				'nullable' => $row['IS_NULLABLE'] === 'YES',
				'default' => $row['COLUMN_DEFAULT'],
				'autoincrement' => false,
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
		$res = $this->driver->query(
			"SELECT ind.name index_name, ind.index_id, ic.index_column_id,
					col.name column_name, ind.is_unique, ind.is_primary_key
			FROM sys.indexes ind
			INNER JOIN sys.index_columns ic ON
				(ind.object_id = ic.object_id AND ind.index_id = ic.index_id)
			INNER JOIN sys.columns col ON
				(ic.object_id = col.object_id and ic.column_id = col.column_id)
			INNER JOIN sys.tables t ON
				(ind.object_id = t.object_id)
			WHERE t.name = {$this->driver->escape($table, dibi::TEXT)}
				AND t.is_ms_shipped = 0
			ORDER BY
				t.name, ind.name, ind.index_id, ic.index_column_id
		");

		$indexes = array();
		while ($row = $res->fetch(TRUE)) {
			$index_name = $row['index_name'];

			if (!isset($indexes[$index_name])) {
				$indexes[$index_name] = array();
				$indexes[$index_name]['name'] = $index_name;
				$indexes[$index_name]['unique'] = (bool)$row['is_unique'];
				$indexes[$index_name]['primary'] = (bool)$row['is_primary_key'];
				$indexes[$index_name]['columns'] = array();
			}
			$indexes[$index_name]['columns'][] = $row['column_name'];
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
		$res = $this->driver->query("
			SELECT f.name AS foreign_key,
			OBJECT_NAME(f.parent_object_id) AS table_name,
			COL_NAME(fc.parent_object_id,
			fc.parent_column_id) AS column_name,
			OBJECT_NAME (f.referenced_object_id) AS reference_table_name,
			COL_NAME(fc.referenced_object_id,
			fc.referenced_column_id) AS reference_column_name,
			fc.*
			FROM sys.foreign_keys AS f
			INNER JOIN sys.foreign_key_columns AS fc
			ON f.OBJECT_ID = fc.constraint_object_id
			WHERE OBJECT_NAME(f.parent_object_id) = {$this->driver->escape($table, dibi::TEXT)}
		");

		$keys = array();
		while ($row = $res->fetch(TRUE)) {
			$key_name = $row['foreign_key'];

			if (!isset($keys[$key_name])) {
				$keys[$key_name]['name'] = $row['foreign_key']; // foreign key name
				$keys[$key_name]['local'] = array($row['column_name']); // local columns
				$keys[$key_name]['table'] = $row['reference_table_name']; // referenced table
				$keys[$key_name]['foreign'] = array($row['reference_column_name']); // referenced columns
				$keys[$key_name]['onDelete'] = false;
				$keys[$key_name]['onUpdate'] = false;
			} else {
				$keys[$key_name]['local'][] = $row['column_name']; // local columns
				$keys[$key_name]['foreign'][] = $row['reference_column_name']; // referenced columns
			}
		}
		return array_values($keys);
	}

}
