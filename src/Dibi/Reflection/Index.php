<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Reflection;



/**
 * Reflection metadata class for a index or primary key.
 *
 * @property-read string $name
 * @property-read array $columns
 * @property-read bool $unique
 * @property-read bool $primary
 */
class Index
{
	public function __construct(
		private readonly array $info,
	) {
	}


	public function getName(): string
	{
		return $this->info['name'];
	}


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
