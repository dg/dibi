<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;


/**
 * Engine-specific behaviors.
 */
interface Engine
{
	/**
	 * Returns list of tables.
	 * @return array of {name [, (bool) view ]}
	 */
	function getTables(): array;

	/**
	 * Returns metadata for all columns in a table.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getColumns(string $table): array;

	/**
	 * Returns metadata for all indexes in a table.
	 * @return array of {name, (array of names) columns [, (bool) unique, (bool) primary ]}
	 */
	function getIndexes(string $table): array;

	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	function getForeignKeys(string $table): array;
}
