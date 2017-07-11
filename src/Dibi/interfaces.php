<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Provides an interface between a dataset and data-aware components.
 */
interface IDataSource extends \Countable, \IteratorAggregate
{
	//function \IteratorAggregate::getIterator();
	//function \Countable::count();
}


/**
 * dibi driver interface.
 */
interface Driver
{

	/**
	 * Connects to a database.
	 * @throws Exception
	 */
	function connect(array &$config): void;

	/**
	 * Disconnects from a database.
	 * @throws Exception
	 */
	function disconnect(): void;

	/**
	 * Internal: Executes the SQL query.
	 * @throws DriverException
	 */
	function query(string $sql): ?ResultDriver;

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	function getAffectedRows(): ?int;

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	function getInsertId(?string $sequence): ?int;

	/**
	 * Begins a transaction (if supported).
	 * @throws DriverException
	 */
	function begin(string $savepoint = NULL): void;

	/**
	 * Commits statements in a transaction.
	 * @throws DriverException
	 */
	function commit(string $savepoint = NULL): void;

	/**
	 * Rollback changes in a transaction.
	 * @throws DriverException
	 */
	function rollback(string $savepoint = NULL): void;

	/**
	 * Returns the connection resource.
	 * @return mixed
	 */
	function getResource();

	/**
	 * Returns the connection reflector.
	 */
	function getReflector(): Reflector;

	/**
	 * Encodes data for use in a SQL statement.
	 */
	function escapeText(string $value): string;

	function escapeBinary(string $value): string;

	function escapeIdentifier(string $value): string;

	function escapeBool(bool $value): string;

	/**
	 * @param  \DateTimeInterface|string|int
	 */
	function escapeDate($value): string;

	/**
	 * @param  \DateTimeInterface|string|int
	 */
	function escapeDateTime($value): string;

	/**
	 * Encodes string for use in a LIKE statement.
	 */
	function escapeLike(string $value, int $pos): string;

	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	function applyLimit(string &$sql, ?int $limit, ?int $offset): void;
}


/**
 * dibi result set driver interface.
 */
interface ResultDriver
{

	/**
	 * Returns the number of rows in a result set.
	 */
	function getRowCount(): int;

	/**
	 * Moves cursor position without fetching row.
	 * @return bool  TRUE on success, FALSE if unable to seek to specified record
	 * @throws Exception
	 */
	function seek(int $row): bool;

	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool          TRUE for associative array, FALSE for numeric
	 * @internal
	 */
	function fetch(bool $type): ?array;

	/**
	 * Frees the resources allocated for this result set.
	 * @param  resource  result set resource
	 */
	function free(): void;

	/**
	 * Returns metadata for all columns in a result set.
	 * @return array of {name, nativetype [, table, fullname, (int) size, (bool) nullable, (mixed) default, (bool) autoincrement, (array) vendor ]}
	 */
	function getResultColumns(): array;

	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	function getResultResource();

	/**
	 * Decodes data from result set.
	 */
	function unescapeBinary(string $value): string;
}


/**
 * dibi driver reflection.
 */
interface Reflector
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
