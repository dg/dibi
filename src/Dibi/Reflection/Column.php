<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;

use Dibi;
use Dibi\Type;


/**
 * Reflection metadata class for a table or result set column.
 *
 * @property-read string $name
 * @property-read string $fullName
 * @property-read Table $table
 * @property-read string $type
 * @property-read mixed $nativeType
 * @property-read int|NULL $size
 * @property-read bool|NULL $unsigned
 * @property-read bool|NULL $nullable
 * @property-read bool|NULL $autoIncrement
 * @property-read mixed $default
 */
class Column
{
	use Dibi\Strict;

	/** @var Dibi\Reflector|NULL when created by Result */
	private $reflector;

	/** @var array (name, nativetype, [table], [fullname], [size], [nullable], [default], [autoincrement], [vendor]) */
	private $info;


	public function __construct(Dibi\Reflector $reflector = NULL, array $info)
	{
		$this->reflector = $reflector;
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
	 * @return string
	 */
	public function getFullName()
	{
		return isset($this->info['fullname']) ? $this->info['fullname'] : NULL;
	}


	/**
	 * @return bool
	 */
	public function hasTable()
	{
		return !empty($this->info['table']);
	}


	/**
	 * @return Table
	 */
	public function getTable()
	{
		if (empty($this->info['table']) || !$this->reflector) {
			throw new Dibi\Exception("Table is unknown or not available.");
		}
		return new Table($this->reflector, ['name' => $this->info['table']]);
	}


	/**
	 * @return string|NULL
	 */
	public function getTableName()
	{
		return isset($this->info['table']) && $this->info['table'] != NULL ? $this->info['table'] : NULL; // intentionally ==
	}


	/**
	 * @return string
	 */
	public function getType()
	{
		return Dibi\Helpers::getTypeCache()->{$this->info['nativetype']};
	}


	/**
	 * @return string
	 */
	public function getNativeType()
	{
		return $this->info['nativetype'];
	}


	/**
	 * @return int|NULL
	 */
	public function getSize()
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : NULL;
	}


	/**
	 * @return bool|NULL
	 */
	public function isUnsigned()
	{
		return isset($this->info['unsigned']) ? (bool) $this->info['unsigned'] : NULL;
	}


	/**
	 * @return bool|NULL
	 */
	public function isNullable()
	{
		return isset($this->info['nullable']) ? (bool) $this->info['nullable'] : NULL;
	}


	/**
	 * @return bool|NULL
	 */
	public function isAutoIncrement()
	{
		return isset($this->info['autoincrement']) ? (bool) $this->info['autoincrement'] : NULL;
	}


	/**
	 * @return mixed
	 */
	public function getDefault()
	{
		return isset($this->info['default']) ? $this->info['default'] : NULL;
	}


	/**
	 * @param  string
	 * @return mixed
	 */
	public function getVendorInfo($key)
	{
		return isset($this->info['vendor'][$key]) ? $this->info['vendor'][$key] : NULL;
	}

}
