<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers;


/**
 * Engine-specific behaviors.
 */
interface Engine
{
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
	 * @return list<array{name: string, columns: string[], unique?: bool, primary?: bool}>
	 */
	function getIndexes(string $table): array;

	/**
	 * Returns metadata for all foreign keys in a table.
	 * @return list<array{name: mixed, table: mixed, column?: mixed, local?: string[], foreign?: string[]|null, onDelete?: string, onUpdate?: string}>
	 */
	function getForeignKeys(string $table): array;
}
