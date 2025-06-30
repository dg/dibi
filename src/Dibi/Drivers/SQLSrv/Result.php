<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\SQLSrv;

use Dibi;
use Dibi\Drivers;
use function is_resource;


/**
 * The driver for Microsoft SQL Server and SQL Azure result set.
 */
class Result implements Drivers\Result
{
	//return values of sqlsrv_field_metadata
	//https://learn.microsoft.com/en-us/sql/connect/php/sqlsrv-field-metadata
	private $sqlsrvDataTypes = array(
		-5=>'SQL_BIGINT',
		-2=>'SQL_BINARY',
		-7=>'SQL_BIT',
		1=>'SQL_CHAR',
		91=>'SQL_TYPE_DATE',
		93=>'SQL_TYPE_TIMESTAMP',
		93=>'SQL_TYPE_TIMESTAMP',
		-155=>'SQL_SS_TIMESTAMPOFFSET',
		3=>'SQL_DECIMAL',
		6=>'SQL_FLOAT',
		-4=>'SQL_LONGVARBINARY',
		4=>'SQL_INTEGER',
		3=>'SQL_DECIMAL',
		-8=>'SQL_WCHAR',
		-10=>'SQL_WLONGVARCHAR',
		2=>'SQL_NUMERIC',
		-9=>'SQL_WVARCHAR',
		7=>'SQL_REAL',
		93=>'SQL_TYPE_TIMESTAMP',
		5=>'SQL_SMALLINT',
		3=>'SQL_DECIMAL',
		-150=>'SQL_SS_VARIANT',
		-1=>'SQL_LONGVARCHAR',
		-154=>'SQL_SS_TIME2',
		-2=>'SQL_BINARY',
		-6=>'SQL_TINYINT',
		-151=>'SQL_SS_UDT',
		-11=>'SQL_GUID',
		-3=>'SQL_VARBINARY',
		12=>'SQL_VARCHAR',
		-152=>'SQL_SS_XML',
	);

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
		return Dibi\Helpers::false2Null(sqlsrv_fetch_array($this->resultSet, $assoc ? SQLSRV_FETCH_ASSOC : SQLSRV_FETCH_NUMERIC));
	}


	/**
	 * Moves cursor position without fetching row.
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
		sqlsrv_free_stmt($this->resultSet);
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$columns = [];
		foreach ((array) sqlsrv_field_metadata($this->resultSet) as $fieldMetadata) {
			$columns[] = [
				'name' => $fieldMetadata['Name'],
				'fullname' => $fieldMetadata['Name'],
				'nativetype' => $this->sqlsrvDataTypes[$fieldMetadata['Type']] ?? 'SQL_VARCHAR',
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
