<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Reflection metadata class for a result set.
 *
 * @package    dibi\reflection
 *
 * @property-read array $columns
 * @property-read array $columnNames
 */
class DibiResultInfo extends DibiObject
{
	/** @var IDibiResultDriver */
	private $driver;

	/** @var array */
	private $columns;

	/** @var array */
	private $names;


	public function __construct(IDibiResultDriver $driver)
	{
		$this->driver = $driver;
	}


	/**
	 * @return DibiColumnInfo[]
	 */
	public function getColumns()
	{
		$this->initColumns();
		return array_values($this->columns);
	}


	/**
	 * @param  bool
	 * @return string[]
	 */
	public function getColumnNames($fullNames = FALSE)
	{
		$this->initColumns();
		$res = array();
		foreach ($this->columns as $column) {
			$res[] = $fullNames ? $column->getFullName() : $column->getName();
		}
		return $res;
	}


	/**
	 * @param  string
	 * @return DibiColumnInfo
	 */
	public function getColumn($name)
	{
		$this->initColumns();
		$l = strtolower($name);
		if (isset($this->names[$l])) {
			return $this->names[$l];

		} else {
			throw new DibiException("Result set has no column '$name'.");
		}
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$this->initColumns();
		return isset($this->names[strtolower($name)]);
	}


	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === NULL) {
			$this->columns = array();
			$reflector = $this->driver instanceof IDibiReflector ? $this->driver : NULL;
			foreach ($this->driver->getResultColumns() as $info) {
				$this->columns[] = $this->names[$info['name']] = new DibiColumnInfo($reflector, $info);
			}
		}
	}

}
