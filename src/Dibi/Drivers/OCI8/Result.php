<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\OCI8;

use Dibi;
use Dibi\Drivers;
use function is_resource;


/**
 * The driver for Oracle result set.
 */
class Result implements Drivers\Result
{
	public function __construct(
		/** @var resource */
		private $resultSet,
	) {
	}


	/**
	 * Returns the number of rows in a result set.
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
		$result = oci_fetch_array($this->resultSet, ($assoc ? OCI_ASSOC : OCI_NUM) | OCI_RETURN_NULLS);

		/*
		* Bug query example: SELECT (SELECT col FROM table WHERE ...) AS subcol FROM dual
		*
		* Oracle by default stream data per n rows (oci8.default_prefetch in php.ini, default is 100).
		* 1) First 100 rows are fetched ok
		* 2) In second fetch call there can be an error (e.g. "ORA-01427: single-row subquery returns more than one row")
			in this case oci_fetch_all returns false so therefore we need to check for errors here
		*/
		$error = oci_error($this->resultSet); 
		if ($error) {
			throw new \Dibi\DriverException($error['message'], $error['code']); 
		}

		return Dibi\Helpers::false2Null($result);
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		oci_free_statement($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = oci_num_fields($this->resultSet);
		$columns = [];
		for ($i = 1; $i <= $count; $i++) {
			$type = oci_field_type($this->resultSet, $i);
			$columns[] = [
				'name' => oci_field_name($this->resultSet, $i),
				'table' => null,
				'fullname' => oci_field_name($this->resultSet, $i),
				'type' => $type === 'LONG' ? Dibi\Type::Text : null,
				'nativetype' => $type === 'NUMBER' && oci_field_scale($this->resultSet, $i) === 0 ? 'INTEGER' : $type,
			];
		}

		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource(): mixed
	{
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
