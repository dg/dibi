<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Reflection;



/**
 * Reflection metadata class for a index or primary key.
 *
 * @property-read string $name
 * @property-read list<Column> $columns
 * @property-read bool $unique
 * @property-read bool $primary
 */
class Index
{
	public function __construct(
		/** @var  array{name: string, columns: list<Column>, unique?: bool, primary?: bool} */
		private readonly array $info,
	) {
	}


	public function getName(): string
	{
		return $this->info['name'];
	}


	/** @return list<Column> */
	public function getColumns(): array
	{
		return $this->info['columns'];
	}


	public function isUnique(): bool
	{
		return !empty($this->info['unique']);
	}


	public function isPrimary(): bool
	{
		return !empty($this->info['primary']);
	}
}
