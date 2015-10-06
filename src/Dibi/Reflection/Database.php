<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Reflection metadata class for a database.
 *
 * @property-read string $name
 * @property-read array $tables
 * @property-read array $tableNames
 */
class DibiDatabaseInfo
{
	use DibiStrict;

	/** @var IDibiReflector */
	private $reflector;

	/** @var string */
	private $name;

	/** @var array */
	private $tables;


	public function __construct(IDibiReflector $reflector, $name)
	{
		$this->reflector = $reflector;
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
	 * @return DibiTableInfo[]
	 */
	public function getTables()
	{
		$this->init();
		return array_values($this->tables);
	}


	/**
	 * @return string[]
	 */
	public function getTableNames()
	{
		$this->init();
		$res = [];
		foreach ($this->tables as $table) {
			$res[] = $table->getName();
		}
		return $res;
	}


	/**
	 * @param  string
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
	 * @param  string
	 * @return bool
	 */
	public function hasTable($name)
	{
		$this->init();
		return isset($this->tables[strtolower($name)]);
	}


	/**
	 * @return void
	 */
	protected function init()
	{
		if ($this->tables === NULL) {
			$this->tables = [];
			foreach ($this->reflector->getTables() as $info) {
				$this->tables[strtolower($info['name'])] = new DibiTableInfo($this->reflector, $info);
			}
		}
	}

}
