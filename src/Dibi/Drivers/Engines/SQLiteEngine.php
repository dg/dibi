<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi\Drivers\Engines;

use Dibi;
use Dibi\Drivers\Connection;
use Dibi\Drivers\Engine;


/**
 * The reflector for SQLite database.
 */
class SQLiteEngine implements Engine
{
	public function __construct(
		private readonly Connection $driver,
		private readonly string $fmtDate = "'Y-m-d'",
		private readonly string $fmtDateTime = "'Y-m-d H:i:s.u'",
	) {
	}


	public function escapeIdentifier(string $value): string
	{
		return '[' . strtr($value, '[]', '  ') . ']';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
	}


	public function escapeDate(\DateTimeInterface $value): string
	{
		return $value->format($this->fmtDate);
	}


	public function escapeDateTime(\DateTimeInterface $value): string
	{
		return $value->format($this->fmtDateTime);
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
		$value = addcslashes(str_replace("'", "''", $value), '%_\\');
		return ($pos & 1 ? "'%" : "'") . $value . ($pos & 2 ? "%'" : "'") . " ESCAPE '\\'";
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');

		} elseif ($limit !== null || $offset) {
			$sql .= ' LIMIT ' . ($limit ?? '-1')
				. ($offset ? ' OFFSET ' . $offset : '');
		}
	}


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->driver->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		") ?? throw new \LogicException('Unexpected null result.');
		$tables = [];
		while ($row = $res->fetch(true)) {
			$tables[] = $row;
		}

		/** @var list<array{name: string, view: bool}> */
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$res = $this->driver->query("PRAGMA table_info({$this->escapeIdentifier($table)})")
			?? throw new \LogicException('Unexpected null result.');
		$columns = [];
		while ($row = $res->fetch(true)) {
			$column = $row['name'];
			$type = explode('(', $row['type']);
			$columns[] = [
				'name' => $column,
				'table' => $table,
				'fullname' => "$table.$column",
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : null,
				'nullable' => $row['notnull'] === 0,
				'default' => $row['dflt_value'],
				'autoincrement' => $row['pk'] && $type[0] === 'INTEGER',
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
		$res = $this->driver->query("PRAGMA index_list({$this->escapeIdentifier($table)})")
			?? throw new \LogicException('Unexpected null result.');
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['name']]['name'] = $row['name'];
			$indexes[$row['name']]['unique'] = (bool) $row['unique'];
			$indexes[$row['name']]['columns'] = [];
		}

		foreach ($indexes as $index => $values) {
			$res = $this->driver->query("PRAGMA index_info({$this->escapeIdentifier($index)})")
				?? throw new \LogicException('Unexpected null result.');
			while ($row = $res->fetch(true)) {
				$indexes[$index]['columns'][$row['seqno']] = $row['name'];
			}
		}

		$columns = $this->getColumns($table);
		foreach ($indexes as $index => $values) {
			$column = $indexes[$index]['columns'][0];
			$primary = false;
			foreach ($columns as $info) {
				if ($column === $info['name']) {
					$primary = $info['vendor']['pk'];
					break;
				}
			}

			$indexes[$index]['primary'] = (bool) $primary;
		}

		if (!$indexes) { // @see http://www.sqlite.org/lang_createtable.html#rowid
			foreach ($columns as $column) {
				if ($column['vendor']['pk'] ?? false) {
					$indexes[] = [
						'name' => 'ROWID',
						'unique' => true,
						'primary' => true,
						'columns' => [$column['name']],
					];
					break;
				}
			}
		}

		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		$res = $this->driver->query("PRAGMA foreign_key_list({$this->escapeIdentifier($table)})")
			?? throw new \LogicException('Unexpected null result.');
		$keys = [];
		while ($row = $res->fetch(true)) {
			$keys[$row['id']]['name'] = $row['id']; // foreign key name
			$keys[$row['id']]['local'][$row['seq']] = $row['from']; // local columns
			$keys[$row['id']]['table'] = $row['table']; // referenced table
			$keys[$row['id']]['foreign'][$row['seq']] = $row['to']; // referenced columns
			$keys[$row['id']]['onDelete'] = $row['on_delete'];
			$keys[$row['id']]['onUpdate'] = $row['on_update'];

			if ($keys[$row['id']]['foreign'][0] == null) {
				$keys[$row['id']]['foreign'] = null;
			}
		}

		return array_values($keys);
	}
}
