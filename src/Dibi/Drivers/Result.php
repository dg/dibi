<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi\Exception;


/**
 * Database result driver.
 */
interface Result
{
	/**
	 * Returns the number of rows in a result set.
	 */
	function getRowCount(): int;

	/**
	 * Moves cursor position without fetching row.
	 * @throws Exception
	 */
	function seek(int $row): bool;

	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $type  true for associative array, false for numeric
	 * @internal
	 */
	function fetch(bool $type): ?array;

	/**
	 * Frees the resources allocated for this result set.
	 */
	function free(): void;

	/**
	 * Returns metadata for all columns in a result set.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getResultColumns(): array;

	/**
	 * Returns the result set resource.
	 */
	function getResultResource(): mixed;

	/**
	 * Decodes data from result set.
	 */
	function unescapeBinary(string $value): string;
}
