<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\Dummy;

use Dibi\Drivers;


/**
 * The driver for no result set.
 */
class Result implements Drivers\Result
{
	public function __construct(
		private readonly int $rows,
	) {
	}


	/**
	 * Returns the number of affected rows.
	 */
	public function getRowCount(): int
	{
		return $this->rows;
	}


	public function fetch(bool $assoc): ?array
	{
		return null;
	}


	public function seek(int $row): bool
	{
		return false;
	}


	public function free(): void
	{
	}


	public function getResultColumns(): array
	{
		return [];
	}


	public function getResultResource(): mixed
	{
		return null;
	}


	public function unescapeBinary(string $value): string
	{
		return $value;
	}
}
