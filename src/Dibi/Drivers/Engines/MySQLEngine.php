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
 * The reflector for MySQL databases.
 * @internal
 */
class MySQLEngine implements Engine
{
	public function __construct(
		private readonly Connection $driver,
	) {
	}


	public function escapeIdentifier(string $value): string
	{
		return '`' . str_replace('`', '``', $value) . '`';
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
		return $value->format("'Y-m-d H:i:s.u'");
	}


	public function escapeDateInterval(\DateInterval $value): string
	{
		if ($value->y || $value->m || $value->d) {
			throw new Dibi\NotSupportedException('Only time interval is supported.');
		}

		return $value->format("'%r%H:%I:%S.%f'");
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$value = addcslashes(str_replace('\\', '\\\\', $value), "\x00\n\r\\'%_");
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'");
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			// see http://dev.mysql.com/doc/refman/5.0/en/select.html
			$sql .= ' LIMIT ' . ($limit ?? '18446744073709551615')
				. ($offset ? ' OFFSET ' . $offset : '');
		}
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->driver->query('SHOW FULL TABLES');
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
		$res = $this->driver->query("SHOW FULL COLUMNS FROM {$this->escapeIdentifier($table)}");
		$columns = [];
		while ($row = $res->fetch(true)) {
			$type = explode('(', $row['Type']);
			$columns[] = [
				'name' => $row['Field'],
				'table' => $table,
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : null,
				'nullable' => $row['Null'] === 'YES',
				'default' => $row['Default'],
				'autoincrement' => $row['Extra'] === 'auto_increment',
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
		$res = $this->driver->query("SHOW INDEX FROM {$this->escapeIdentifier($table)}");
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['Key_name']]['name'] = $row['Key_name'];
			$indexes[$row['Key_name']]['unique'] = !$row['Non_unique'];
			$indexes[$row['Key_name']]['primary'] = $row['Key_name'] === 'PRIMARY';
			$indexes[$row['Key_name']]['columns'][$row['Seq_in_index'] - 1] = $row['Column_name'];
		}

		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 * @throws Dibi\NotSupportedException
	 */
	public function getForeignKeys(string $table): array
	{
		$data = $this->driver->query("SELECT `ENGINE` FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = {$this->driver->escapeText($table)}")->fetch(true);
		if ($data['ENGINE'] !== 'InnoDB') {
			throw new Dibi\NotSupportedException("Foreign keys are not supported in {$data['ENGINE']} tables.");
		}

		$res = $this->driver->query("
			SELECT rc.CONSTRAINT_NAME, rc.UPDATE_RULE, rc.DELETE_RULE, kcu.REFERENCED_TABLE_NAME,
				GROUP_CONCAT(kcu.REFERENCED_COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS REFERENCED_COLUMNS,
				GROUP_CONCAT(kcu.COLUMN_NAME ORDER BY kcu.ORDINAL_POSITION) AS COLUMNS
			FROM information_schema.REFERENTIAL_CONSTRAINTS rc
			INNER JOIN information_schema.KEY_COLUMN_USAGE kcu ON
				kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
				AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
			WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
				AND rc.TABLE_NAME = {$this->driver->escapeText($table)}
			GROUP BY rc.CONSTRAINT_NAME
		");

		$foreignKeys = [];
		while ($row = $res->fetch(true)) {
			$keyName = $row['CONSTRAINT_NAME'];

			$foreignKeys[$keyName]['name'] = $keyName;
			$foreignKeys[$keyName]['local'] = explode(',', $row['COLUMNS']);
			$foreignKeys[$keyName]['table'] = $row['REFERENCED_TABLE_NAME'];
			$foreignKeys[$keyName]['foreign'] = explode(',', $row['REFERENCED_COLUMNS']);
			$foreignKeys[$keyName]['onDelete'] = $row['DELETE_RULE'];
			$foreignKeys[$keyName]['onUpdate'] = $row['UPDATE_RULE'];
		}

		return array_values($foreignKeys);
	}
}
