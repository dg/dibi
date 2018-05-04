<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;
use Dibi\Helpers;


/**
 * The dibi driver for PostgreSQL  result set.
 */
class PostgreResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var resource */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;


	/**
	 * @param  resource  $resultSet
	 */
	public function __construct($resultSet)
	{
		$this->resultSet = $resultSet;
	}


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		if ($this->autoFree && $this->getResultResource()) {
			$this->free();
		}
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		return pg_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null(pg_fetch_array($this->resultSet, null, $assoc ? PGSQL_ASSOC : PGSQL_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		return pg_result_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		pg_free_result($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = pg_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = [
				'name' => pg_field_name($this->resultSet, $i),
				'table' => pg_field_table($this->resultSet, $i),
				'nativetype' => pg_field_type($this->resultSet, $i),
			];
			$row['fullname'] = $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'];
			$columns[] = $row;
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource()
	{
		$this->autoFree = false;
		return is_resource($this->resultSet) ? $this->resultSet : null;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return pg_unescape_bytea($value);
	}
}
