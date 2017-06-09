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


	public function getName(): string
	{
		return $this->info['name'];
	}


	public function getFullName(): string
	{
		return $this->info['fullname'] ?? NULL;
	}


	public function hasTable(): bool
	{
		return !empty($this->info['table']);
	}


	public function getTable(): Table
	{
		if (empty($this->info['table']) || !$this->reflector) {
			throw new Dibi\Exception("Table is unknown or not available.");
		}
		return new Table($this->reflector, ['name' => $this->info['table']]);
	}


	public function getTableName(): ?string
	{
		return isset($this->info['table']) && $this->info['table'] != NULL ? $this->info['table'] : NULL; // intentionally ==
	}


	public function getType(): string
	{
		return Dibi\Helpers::getTypeCache()->{$this->info['nativetype']};
	}


	public function getNativeType(): string
	{
		return $this->info['nativetype'];
	}


	public function getSize(): ?int
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : NULL;
	}


	public function isUnsigned(): ?bool
	{
		return isset($this->info['unsigned']) ? (bool) $this->info['unsigned'] : NULL;
	}


	public function isNullable(): ?bool
	{
		return isset($this->info['nullable']) ? (bool) $this->info['nullable'] : NULL;
	}


	public function isAutoIncrement(): ?bool
	{
		return isset($this->info['autoincrement']) ? (bool) $this->info['autoincrement'] : NULL;
	}


	/**
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->info['default'] ?? NULL;
	}


	/**
	 * @return mixed
	 */
	public function getVendorInfo(string $key)
	{
		return $this->info['vendor'][$key] ?? NULL;
	}

}
