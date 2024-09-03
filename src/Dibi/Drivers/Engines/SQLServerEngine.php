<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\Engines;

use Dibi;
use Dibi\Drivers\Connection;
use Dibi\Drivers\Engine;


/**
 * The reflector for Microsoft SQL Server and SQL Azure databases.
 */
class SQLServerEngine implements Engine
{
	public function __construct(
		private readonly Connection $driver,
	) {
	}


	public function escapeIdentifier(string $value): string
	{
		// @see https://msdn.microsoft.com/en-us/library/ms176027.aspx
		return '[' . str_replace(']', ']]', $value) . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format("'Y-m-d'");
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return 'CONVERT(DATETIME2(7), ' . $value->format("'Y-m-d H:i:s.u'") . ')';
	}


	public function escapeDateInterval(\DateInterval $value): string
	{
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = strtr($value, ["'" => "''", '%' => '[%]', '_' => '[_]', '[' => '[[]']);
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', rtrim($sql), $offset, $limit);
		} elseif ($offset) {
			// requires ORDER BY, see https://technet.microsoft.com/en-us/library/gg699618(v=sql.110).aspx
			$sql = sprintf('%s OFFSET %d ROWS', rtrim($sql), $offset);
		}
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->driver->query("SELECT TABLE_NAME, TABLE_TYPE FROM INFORMATION_SCHEMA.TABLES WHERE [TABLE_SCHEMA] = 'dbo'");
		$tables = [];
		while ($row = $res->fetch(false)) {
			$tables[] = [
				'name' => $row[0],
				'view' => isset($row[1]) && $row[1] === 'VIEW',
			];
		}

		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->driver->query("
			SELECT c.name as COLUMN_NAME, c.is_identity AS AUTO_INCREMENT
			FROM sys.columns c
			INNER JOIN sys.tables t ON c.object_id = t.object_id
			WHERE t.name = {$this->driver->escapeText($table)}
		");

		$autoIncrements = [];
		while ($row = $res->fetch(true)) {
			$autoIncrements[$row['COLUMN_NAME']] = (bool) $row['AUTO_INCREMENT'];
		}

		$res = $this->driver->query("
			SELECT C.COLUMN_NAME, C.DATA_TYPE, C.CHARACTER_MAXIMUM_LENGTH , C.COLUMN_DEFAULT  , C.NUMERIC_PRECISION, C.NUMERIC_SCALE , C.IS_NULLABLE, Case When Z.CONSTRAINT_NAME Is Null Then 0 Else 1 End As IsPartOfPrimaryKey
			FROM INFORMATION_SCHEMA.COLUMNS As C
			Outer Apply (
				SELECT CCU.CONSTRAINT_NAME
				FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS As TC
				Join INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE As CCU
					On CCU.CONSTRAINT_NAME = TC.CONSTRAINT_NAME
				WHERE TC.TABLE_SCHEMA = C.TABLE_SCHEMA
					And TC.TABLE_NAME = C.TABLE_NAME
					And TC.CONSTRAINT_TYPE = 'PRIMARY KEY'
					And CCU.COLUMN_NAME = C.COLUMN_NAME
			) As Z
			WHERE C.TABLE_NAME = {$this->driver->escapeText($table)}
		");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[] = [
				'name' => $row['COLUMN_NAME'],
				'table' => $table,
				'nativetype' => strtoupper($row['DATA_TYPE']),
				'size' => $row['CHARACTER_MAXIMUM_LENGTH'],
				'nullable' => $row['IS_NULLABLE'] === 'YES',
				'default' => $row['COLUMN_DEFAULT'],
				'autoincrement' => $autoIncrements[$row['COLUMN_NAME']],
				'vendor' => $row,
			];
		}

		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table.
	 */
	public function getIndexes(string $table): array
	{
		$keyUsagesRes = $this->driver->query(sprintf('EXEC [sys].[sp_helpindex] @objname = %s', $this->driver->escapeText($table)));
		$keyUsages = [];
		while ($row = $keyUsagesRes->fetch(true)) {
			$keyUsages[$row['index_name']] = explode(',', $row['index_keys']);
		}

		$res = $this->driver->query("SELECT [i].* FROM [sys].[indexes] [i] INNER JOIN [sys].[tables] [t] ON [i].[object_id] = [t].[object_id] WHERE [t].[name] = {$this->driver->escapeText($table)}");
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = $row['is_unique'] === 1;
			$indexes[$row['name']]['primary'] = $row['is_primary_key'] === 1;
			$indexes[$row['name']]['columns'] = $keyUsages[$row['name']] ?? [];
		}

		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}
}
