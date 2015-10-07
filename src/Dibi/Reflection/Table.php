<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Reflection metadata class for a database table.
 *
 * @package    dibi\reflection
 *
 * @property-read string $name
 * @property-read bool $view
 * @property-read array $columns
 * @property-read array $columnNames
 * @property-read array $foreignKeys
 * @property-read array $indexes
 * @property-read DibiIndexInfo $primaryKey
 */
class DibiTableInfo extends DibiObject
{
	/** @var IDibiReflector */
	private $reflector;

	/** @var string */
	private $name;

	/** @var bool */
	private $view;

	/** @var array */
	private $columns;

	/** @var array */
	private $foreignKeys;

	/** @var array */
	private $indexes;

	/** @var DibiIndexInfo */
	private $primaryKey;


	public function __construct(IDibiReflector $reflector, array $info)
	{
		$this->reflector = $reflector;
		$this->name = $info['name'];
		$this->view = !empty($info['view']);
	}


	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * @return bool
	 */
	public function isView()
	{
		return $this->view;
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
	 * @return string[]
	 */
	public function getColumnNames()
	{
		$this->initColumns();
		$res = array();
		foreach ($this->columns as $column) {
			$res[] = $column->getName();
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
		if (isset($this->columns[$l])) {
			return $this->columns[$l];

		} else {
			throw new DibiException("Table '$this->name' has no column '$name'.");
		}
	}


	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$this->initColumns();
		return isset($this->columns[strtolower($name)]);
	}


	/**
	 * @return DibiForeignKeyInfo[]
	 */
	public function getForeignKeys()
	{
		$this->initForeignKeys();
		return $this->foreignKeys;
	}


	/**
	 * @return DibiIndexInfo[]
	 */
	public function getIndexes()
	{
		$this->initIndexes();
		return $this->indexes;
	}


	/**
	 * @return DibiIndexInfo
	 */
	public function getPrimaryKey()
	{
		$this->initIndexes();
		return $this->primaryKey;
	}


	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === NULL) {
			$this->columns = array();
			foreach ($this->reflector->getColumns($this->name) as $info) {
				$this->columns[strtolower($info['name'])] = new DibiColumnInfo($this->reflector, $info);
			}
		}
	}


	/**
	 * @return void
	 */
	protected function initIndexes()
	{
		if ($this->indexes === NULL) {
			$this->initColumns();
			$this->indexes = array();
			foreach ($this->reflector->getIndexes($this->name) as $info) {
				foreach ($info['columns'] as $key => $name) {
					$info['columns'][$key] = $this->columns[strtolower($name)];
				}
				$this->indexes[strtolower($info['name'])] = new DibiIndexInfo($info);
				if (!empty($info['primary'])) {
					$this->primaryKey = $this->indexes[strtolower($info['name'])];
				}
			}
		}
	}


	/**
	 * @return void
	 */
	protected function initForeignKeys()
	{
		throw new DibiNotImplementedException;
	}

}
