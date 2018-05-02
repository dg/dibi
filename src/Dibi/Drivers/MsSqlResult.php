<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers;

use Dibi;


/**
 * The dibi driver for MS SQL result set.
 */
class MsSqlResult implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var resource|null */
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
		return mssql_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc   true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Dibi\Helpers::false2Null(mssql_fetch_array($this->resultSet, $assoc ? MSSQL_ASSOC : MSSQL_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
	 * @return bool  true on success, false if unable to seek to specified record
	 */
	public function seek(int $row): bool
	{
		return mssql_data_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		mssql_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = mssql_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) mssql_fetch_field($this->resultSet, $i);
			$columns[] = [
				'name' => $row['name'],
				'fullname' => $row['column_source'] ? $row['column_source'] . '.' . $row['name'] : $row['name'],
				'table' => $row['column_source'],
				'nativetype' => $row['type'],
			];
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
		return $value;
	}
}
