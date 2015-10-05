<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Reflection metadata class for a table or result set column.
 *
 * @package    dibi\reflection
 *
 * @property-read string $name
 * @property-read string $fullName
 * @property-read DibiTableInfo $table
 * @property-read string $type
 * @property-read mixed $nativeType
 * @property-read int $size
 * @property-read bool $unsigned
 * @property-read bool $nullable
 * @property-read bool $autoIncrement
 * @property-read mixed $default
 */
class DibiColumnInfo extends DibiObject
{
	/** @var array */
	private static $types;

	/** @var IDibiReflector|NULL when created by DibiResultInfo */
	private $reflector;

	/** @var array (name, nativetype, [table], [fullname], [size], [nullable], [default], [autoincrement], [vendor]) */
	private $info;


	public function __construct(IDibiReflector $reflector = NULL, array $info)
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
	 * @return DibiTableInfo
	 */
	public function getTable()
	{
		if (empty($this->info['table']) || !$this->reflector) {
			throw new DibiException("Table is unknown or not available.");
		}
		return new DibiTableInfo($this->reflector, ['name' => $this->info['table']]);
	}


	/**
	 * @return string
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
		return self::getTypeCache()->{$this->info['nativetype']};
	}


	/**
	 * @return mixed
	 */
	public function getNativeType()
	{
		return $this->info['nativetype'];
	}


	/**
	 * @return int
	 */
	public function getSize()
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : NULL;
	}


	/**
	 * @return bool
	 */
	public function isUnsigned()
	{
		return isset($this->info['unsigned']) ? (bool) $this->info['unsigned'] : NULL;
	}


	/**
	 * @return bool
	 */
	public function isNullable()
	{
		return isset($this->info['nullable']) ? (bool) $this->info['nullable'] : NULL;
	}


	/**
	 * @return bool
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


	/**
	 * Heuristic type detection.
	 * @param  string
	 * @return string
	 * @internal
	 */
	public static function detectType($type)
	{
		static $patterns = [
			'^_' => dibi::TEXT, // PostgreSQL arrays
			'BYTEA|BLOB|BIN' => dibi::BINARY,
			'TEXT|CHAR|POINT|INTERVAL' => dibi::TEXT,
			'YEAR|BYTE|COUNTER|SERIAL|INT|LONG|SHORT' => dibi::INTEGER,
			'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER' => dibi::FLOAT,
			'^TIME$' => dibi::TIME,
			'TIME' => dibi::DATETIME, // DATETIME, TIMESTAMP
			'DATE' => dibi::DATE,
			'BOOL' => dibi::BOOL,
		];

		foreach ($patterns as $s => $val) {
			if (preg_match("#$s#i", $type)) {
				return $val;
			}
		}
		return dibi::TEXT;
	}


	/**
	 * @internal
	 */
	public static function getTypeCache()
	{
		if (self::$types === NULL) {
			self::$types = new DibiHashMap([__CLASS__, 'detectType']);
		}
		return self::$types;
	}

}
