<?php

/**
 * Adapter for database drivers.
 * @see IDibiDriver
 * @see IDibiResultDriver
 * @see IDibiReflector
 * @see DibiObject
 */
class DibiDriverAdapter extends DibiObject implements IDibiDriver, IDibiResultDriver, IDibiReflector
{

	public function config(array &$config)
	{
	}

	public function applyLimit(&$sql, $limit, $offset)
	{
	}

	public function begin($savepoint = NULL)
	{
	}

	public function commit($savepoint = NULL)
	{
	}

	public function connect(array &$config)
	{
	}

	public function disconnect()
	{
	}

	public function escape($value, $type)
	{
	}

	public function escapeLike($value, $pos)
	{
	}

	public function fetch($type)
	{
	}

	public function free()
	{
	}

	public function getAffectedRows()
	{
	}

	public function getColumns($table)
	{
	}

	public function getForeignKeys($table)
	{
	}

	public function getIndexes($table)
	{
	}

	public function getInsertId($sequence)
	{
	}

	public function getReflector()
	{
	}

	public function getResource()
	{
	}

	public function getResultColumns()
	{
	}

	public function getResultResource()
	{
	}

	public function getRowCount()
	{
	}

	public function getTables()
	{
	}

	public function query($sql)
	{
	}

	public function rollback($savepoint = NULL)
	{
	}

	public function seek($row)
	{
	}

	public function unescape($value, $type)
	{
	}

}

?>
