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
 * The dibi driver for SQLite3 result set.
 */
class Sqlite3Result implements Dibi\ResultDriver
{
	use Dibi\Strict;

	/** @var \SQLite3Result */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;


	public function __construct(\SQLite3Result $resultSet)
	{
		$this->resultSet = $resultSet;
	}


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		if ($this->autoFree && $this->getResultResource()) {
			@$this->free();
		}
	}


	/**
	 * Returns the number of rows in a result set.
	 * @throws Dibi\NotSupportedException
	 */
	public function getRowCount(): int
	{
		throw new Dibi\NotSupportedException('Row count is not available for unbuffered queries.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool  $assoc  true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null($this->resultSet->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\NotSupportedException
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotSupportedException('Cannot seek an unbuffered result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		$this->resultSet->finalize();
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = $this->resultSet->numColumns();
		$columns = [];
		static $types = [SQLITE3_INTEGER => 'int', SQLITE3_FLOAT => 'float', SQLITE3_TEXT => 'text', SQLITE3_BLOB => 'blob', SQLITE3_NULL => 'null'];
		for ($i = 0; $i < $count; $i++) {
			$columns[] = [
				'name' => $this->resultSet->columnName($i),
				'table' => null,
				'fullname' => $this->resultSet->columnName($i),
				'nativetype' => $types[$this->resultSet->columnType($i)],
			];
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 */
	public function getResultResource(): \SQLite3Result
	{
		$this->autoFree = false;
		return $this->resultSet;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}
}
