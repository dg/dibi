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
 * Reflection metadata class for a database.
 * @package dibi
 */
class DibiDatabaseInfo extends DibiObject
{
	/** @var IDibiDriver */
	private $driver;

	/** @var string */
	private $name;

	/** @var array */
	private $tables;



	public function __construct(IDibiDriver $driver, $name)
	{
		$this->driver = $driver;
		$this->name = $name;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return array of DibiTableInfo
	 */
	public function getTables()
	{
		$this->init();
		return array_values($this->tables);
	}



	/**
	 * @return array of string
	 */
	public function getTableNames()
	{
		$this->init();
		$res = array();
		foreach ($this->tables as $table) {
			$res[] = $table->getName();
		}
		return $res;
	}



	/**
	 * @param string
	 * @return DibiTableInfo
	 */
	public function getTable($name)
	{
		$this->init();
		$l = strtolower($name);
		if (isset($this->tables[$l])) {
			return $this->tables[$l];

		} else {
			throw new DibiException("Database '$this->name' has no table '$name'.");
		}
	}



	/**
	 * @param string
	 * @return bool
	 */
	public function hasTable($name)
	{
		$this->init();
		return isset($this->tables[strtolower($name)]);
	}



	/**
	 * @return array
	 */
	public function getSequences()
	{
		throw new NotImplementedException;
	}



	/**
	 * @return void
	 */
	protected function init()
	{
		if ($this->tables === NULL) {
			$this->tables = array();
			foreach ($this->driver->getTables() as $info) {
				$this->tables[strtolower($info['name'])] = new DibiTableInfo($this->driver, $info);
			}
		}
	}

}




/**
 * Reflection metadata class for a database table.
 * @package dibi
 */
class DibiTableInfo extends DibiObject
{
	/** @var IDibiDriver */
	private $driver;

	/** @var array */
	private $info;

	/** @var array */
	private $columns;

	/** @var array */
	private $foreignKeys;

	/** @var array */
	private $indexes;

	/** @var DibiIndexInfo */
	private $primaryKey;



	public function __construct(IDibiDriver $driver, array $info)
	{
		$this->driver = $driver;
		$this->info = $info;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->info['name'];
	}



	/**
	 * @return bool
	 */
	public function isView()
	{
		return !empty($this->info['view']);
	}



	/**
	 * @return array of DibiColumnInfo
	 */
	public function getColumns()
	{
		$this->initColumns();
		return array_values($this->columns);
	}



	/**
	 * @return array of string
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
	 * @param string
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
	 * @param string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$this->initColumns();
		return isset($this->columns[strtolower($name)]);
	}



	/**
	 * @return array of DibiForeignKeyInfo
	 */
	public function getForeignKeys()
	{
		$this->initForeignKeys();
		return $this->foreignKeys;
	}



	/**
	 * @return array of DibiIndexInfo
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
			foreach ($this->driver->getColumns($this->name) as $info) {
				$this->columns[strtolower($info['name'])] = new DibiColumnInfo($this->driver, $info);
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
			foreach ($this->driver->getIndexes($this->name) as $info) {
				$cols = array();
				foreach ($info['columns'] as $name) {
					$cols[] = $this->columns[strtolower($name)];
				}
				$name = $info['name'];
				$this->indexes[strtolower($name)] = new DibiIndexInfo($this, $name, $cols, $info['unique']);
				if (!empty($info['primary'])) {
					$this->primaryKey = $this->indexes[strtolower($name)];
				}
			}
		}
	}



	/**
	 * @return void
	 */
	protected function initForeignKeys()
	{
		throw new NotImplementedException;
	}

}




/**
 * Reflection metadata class for a table column.
 * @package dibi
 */
class DibiColumnInfo extends DibiObject
{
	/** @var IDibiDriver */
	private $driver;

	/** @var array (name, table, type, nativetype, size, precision, scale, nullable, default, autoincrement) */
	private $info;



	public function __construct(IDibiDriver $driver, array $info)
	{
		$this->driver = $driver;
		$this->info = $info;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->info['name'];
	}



	/**
	 * @return DibiTableInfo
	 */
	public function getTable()
	{
		if (empty($this->info['table'])) {
			throw new DibiException("Table name is unknown.");
		}
		return new DibiTableInfo($this->driver, array('name' => $this->info['table']));
	}



	/**
	 * @return string
	 */
	public function getType()
	{
		return isset($this->info['type']) ? $this->info['type'] : NULL;
	}



	/**
	 * @return mixed
	 */
	public function getNativeType()
	{
		return isset($this->info['nativetype']) ? $this->info['nativetype'] : NULL;
	}



	/**
	 * @return int
	 */
	public function getSize()
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : NULL;
	}



	/**
	 * @return int
	 */
	public function getPrecision()
	{
		return isset($this->info['precision']) ? (int) $this->info['precision'] : NULL;
	}



	/**
	 * @return int
	 */
	public function getScale()
	{
		return isset($this->info['scale']) ? (int) $this->info['scale'] : NULL;
	}



	/**
	 * @return bool
	 */
	public function isNullable()
	{
		return !empty($this->info['nullable']);
	}



	/**
	 * @return bool
	 */
	public function isAutoIncrement()
	{
		return !empty($this->info['autoincrement']);
	}



	/**
	 * @return mixed
	 */
	public function getDefaultValue()
	{
		return isset($this->info['default']) ? $this->info['default'] : NULL;
	}

}




/**
 * Reflection metadata class for a foreign key.
 * @package dibi
 */
class DibiForeignKeyInfo extends DibiObject
{
	/** @var string */
	private $name;

	/** @var array of array(local, foreign, onDelete, onUpdate) */
	private $references;



	public function __construct($name, array $references)
	{
		$this->name = $name;
		$this->references = $references;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return array
	 */
	public function getReferences()
	{
		return $this->references;
	}

}




/**
 * Reflection metadata class for a index or primary key
 * @package dibi
 */
class DibiIndexInfo extends DibiObject
{
	/** @var string */
	private $name;

	/** @var array of DibiColumnInfo */
	private $columns;

	/** @var bool */
	private $unique;



	public function __construct($name, array $columns, $unique)
	{
		$this->name = $name;
		$this->columns = $columns;
		$this->unique = (bool) $unique;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}



	/**
	 * @return bool
	 */
	public function isUnique()
	{
		return $this->unique;
	}

}
