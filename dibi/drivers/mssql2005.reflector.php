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
 * The dibi reflector for MSSQL2005 databases.
 *
 * @author     Daniel Kouba
 * @package    dibi\drivers
 * @internal
 */
class DibiMssql2005Reflector extends DibiObject implements IDibiReflector
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
		$res = $this->driver->query('SELECT TABLE_NAME, TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES');
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
	1f9fe4d4fac92d264d7d9f4d9ef7abd8e2483c38



	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		$keyUsagesRes = $this->driver->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = {$this->driver->escape($table, dibi::TEXT)}");
		$keyUsages = array();
		while( $row = $keyUsagesRes->fetch(TRUE) ) {
			$keyUsages[$row['CONSTRAINT_NAME']][(int) $row['ORDINAL_POSITION'] - 1] = $row['COLUMN_NAME'];
		}

		$res = $this->driver->query("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = {$this->driver->escape($table, dibi::TEXT)}");
		$indexes = array();
		while ($row = $res->fetch(TRUE)) {
			$indexes[$row['CONSTRAINT_NAME']]['name'] = $row['CONSTRAINT_NAME'];
			$indexes[$row['CONSTRAINT_NAME']]['unique'] = $row['CONSTRAINT_TYPE'] === 'UNIQUE';
			$indexes[$row['CONSTRAINT_NAME']]['primary'] = $row['CONSTRAINT_TYPE'] === 'PRIMARY KEY';
			$indexes[$row['CONSTRAINT_NAME']]['columns'] = isset($keyUsages[$row['CONSTRAINT_NAME']]) ? $keyUsages[$row['CONSTRAINT_NAME']] : array();
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
