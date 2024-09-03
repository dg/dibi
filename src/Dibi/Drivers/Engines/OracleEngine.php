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
 * The reflector for Oracle database.
 */
class OracleEngine implements Engine
{
	public function __construct(
		private readonly Connection $driver,
	) {
	}


	public function escapeIdentifier(string $value): string
	{
		// @see http://download.oracle.com/docs/cd/B10500_01/server.920/a96540/sql_elements9a.htm
		return '"' . str_replace('"', '""', $value) . '"';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $this->nativeDate // TODO
			? "to_date('" . $value->format('Y-m-d') . "', 'YYYY-mm-dd')"
			: $value->format('U');
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return $this->nativeDate // TODO
			? "to_date('" . $value->format('Y-m-d G:i:s') . "', 'YYYY-mm-dd hh24:mi:ss')"
			: $value->format('U');
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
		$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\\%_");
		$value = str_replace("'", "''", $value);
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($offset) {
			// see http://www.oracle.com/technology/oramag/oracle/06-sep/o56asktom.html
			$sql = 'SELECT * FROM (SELECT t.*, ROWNUM AS "__rnum" FROM (' . $sql . ') t '
				. ($limit !== null ? 'WHERE ROWNUM <= ' . ($offset + $limit) : '')
				. ') WHERE "__rnum" > ' . $offset;

		} elseif ($limit !== null) {
			$sql = 'SELECT * FROM (' . $sql . ') WHERE ROWNUM <= ' . $limit;
		}
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->driver->query('SELECT * FROM cat');
		$tables = [];
		while ($row = $res->fetch(false)) {
			if ($row[1] === 'TABLE' || $row[1] === 'VIEW') {
				$tables[] = [
					'name' => $row[0],
					'view' => $row[1] === 'VIEW',
				];
			}
		}

		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->driver->query('SELECT * FROM "ALL_TAB_COLUMNS" WHERE "TABLE_NAME" = ' . $this->driver->escapeText($table));
		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[] = [
				'table' => $row['TABLE_NAME'],
				'name' => $row['COLUMN_NAME'],
				'nativetype' => $row['DATA_TYPE'],
				'size' => $row['DATA_LENGTH'] ?? null,
				'nullable' => $row['NULLABLE'] === 'Y',
				'default' => $row['DATA_DEFAULT'],
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
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		throw new Dibi\NotImplementedException;
	}
}
