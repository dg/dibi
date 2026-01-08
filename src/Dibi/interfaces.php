<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * Provides an interface between a dataset and data-aware components.
 * @extends \IteratorAggregate<int, Row|mixed[]>
 */
interface IDataSource extends \Countable, \IteratorAggregate
{
	//function \IteratorAggregate::getIterator();
	//function \Countable::count();
}


/**
 * Driver interface.
 */
interface Driver
{
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
	function begin(?string $savepoint = null): void;

	/**
	 * Commits statements in a transaction.
	 * @throws DriverException
	 */
	function commit(?string $savepoint = null): void;

	/**
	 * Rollback changes in a transaction.
	 * @throws DriverException
	 */
	function rollback(?string $savepoint = null): void;

	/**
	 * Returns the connection resource.
	 */
	function getResource(): mixed;

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

	function escapeDate(\DateTimeInterface $value): string;

	function escapeDateTime(\DateTimeInterface $value): string;

	function escapeDateInterval(\DateInterval $value): string;

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
 * Result set driver interface.
 */
interface ResultDriver
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
	 * @return mixed[]|null
	 * @internal
	 */
	function fetch(bool $type): ?array;

	/**
	 * Frees the resources allocated for this result set.
	 */
	function free(): void;

	/**
	 * Returns metadata for all columns in a result set.
	 * @return list<array{name: string, nativetype: string, table?: ?string, fullname?: ?string, type?: ?string, vendor?: mixed[]}>
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


/**
 * Reflection driver.
 */
interface Reflector
{
	/**
	 * Returns list of tables.
	 * @return list<array{name: string, view: bool}>
	 */
	function getTables(): array;

	/**
	 * Returns metadata for all columns in a table.
	 * @return list<array{name: string, nativetype: string, table?: string, fullname?: string, size?: ?int, nullable?: bool, default?: mixed, autoincrement?: bool, vendor?: array<string, mixed>}>
	 */
	function getColumns(string $table): array;

	/**
	 * Returns metadata for all indexes in a table.
	 * @return list<array{name: string, columns: list<string>, unique?: bool, primary?: bool}>
	 */
	function getIndexes(string $table): array;

	/**
	 * Returns metadata for all foreign keys in a table.
	 * @return list<array{name: mixed, table: mixed, column?: mixed, local?: string[], foreign?: string[]|null, onDelete?: string, onUpdate?: string}>
	 */
	function getForeignKeys(string $table): array;
}


/**
 * Dibi connection.
 */
interface IConnection
{
	/**
	 * Connects to a database.
	 */
	function connect(): void;

	/**
	 * Disconnects from a database.
	 */
	function disconnect(): void;

	/**
	 * Returns true when connection was established.
	 */
	function isConnected(): bool;

	/**
	 * Returns the driver and connects to a database in lazy mode.
	 */
	function getDriver(): Driver;

	/**
	 * Generates (translates) and executes SQL query.
	 * @throws Exception
	 */
	function query(mixed ...$args): Result;

	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 * @throws Exception
	 */
	function getAffectedRows(): int;

	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 * @throws Exception
	 */
	function getInsertId(?string $sequence = null): int;

	/**
	 * Begins a transaction (if supported).
	 */
	function begin(?string $savepoint = null): void;

	/**
	 * Commits statements in a transaction.
	 */
	function commit(?string $savepoint = null): void;

	/**
	 * Rollback changes in a transaction.
	 */
	function rollback(?string $savepoint = null): void;
}
