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
 * The dibi driver for PostgreSQL database.
 *
 * Driver options:
 *   - host, hostaddr, port, dbname, user, password, connect_timeout, options, sslmode, service => see PostgreSQL API
 *   - string => or use connection string
 *   - schema => the schema search path
 *   - charset => character encoding to set (default is utf8)
 *   - persistent (bool) => try to find a persistent link?
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class PostgreDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var int|null  Affected rows */
	private $affectedRows;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('pgsql')) {
			throw new Dibi\NotSupportedException("PHP extension 'pgsql' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		$error = null;
		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			$config += [
				'charset' => 'utf8',
			];
			if (isset($config['string'])) {
				$string = $config['string'];
			} else {
				$string = '';
				Helpers::alias($config, 'user', 'username');
				Helpers::alias($config, 'dbname', 'database');
				foreach (['host', 'hostaddr', 'port', 'dbname', 'user', 'password', 'connect_timeout', 'options', 'sslmode', 'service'] as $key) {
					if (isset($config[$key])) {
						$string .= $key . '=' . $config[$key] . ' ';
					}
				}
			}

			set_error_handler(function ($severity, $message) use (&$error) {
				$error = $message;
			});
			if (empty($config['persistent'])) {
				$this->connection = pg_connect($string, PGSQL_CONNECT_FORCE_NEW);
			} else {
				$this->connection = pg_pconnect($string, PGSQL_CONNECT_FORCE_NEW);
			}
			restore_error_handler();
		}

		if (!is_resource($this->connection)) {
			throw new Dibi\DriverException($error ?: 'Connecting error.');
		}

		pg_set_error_verbosity($this->connection, PGSQL_ERRORS_VERBOSE);

		if (isset($config['charset']) && pg_set_client_encoding($this->connection, $config['charset'])) {
			throw self::createException(pg_last_error($this->connection));
		}

		if (isset($config['schema'])) {
			$this->query('SET search_path TO "' . implode('", "', (array) $config['schema']) . '"');
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@pg_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Pings database.
	 */
	public function ping(): bool
	{
		return pg_ping($this->connection);
	}


	/**
	 * Executes the SQL query.
	 * @param  string      SQL statement.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$this->affectedRows = null;
		$res = @pg_query($this->connection, $sql); // intentionally @

		if ($res === false) {
			throw self::createException(pg_last_error($this->connection), null, $sql);

		} elseif (is_resource($res)) {
			$this->affectedRows = Helpers::false2Null(pg_affected_rows($res));
			if (pg_num_fields($res)) {
				return $this->createResultDriver($res);
			}
		}
		return null;
	}


	public static function createException(string $message, $code = null, string $sql = null): Dibi\DriverException
	{
		if ($code === null && preg_match('#^ERROR:\s+(\S+):\s*#', $message, $m)) {
			$code = $m[1];
			$message = substr($message, strlen($m[0]));
		}

		if ($code === '0A000' && strpos($message, 'truncate') !== false) {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23502') {
			return new Dibi\NotNullConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23503') {
			return new Dibi\ForeignKeyConstraintViolationException($message, $code, $sql);

		} elseif ($code === '23505') {
			return new Dibi\UniqueConstraintViolationException($message, $code, $sql);

		} else {
			return new Dibi\DriverException($message, $code, $sql);
		}
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return $this->affectedRows;
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		if ($sequence === null) {
			// PostgreSQL 8.1 is needed
			$res = $this->query('SELECT LASTVAL()');
		} else {
			$res = $this->query("SELECT CURRVAL('$sequence')");
		}

		if (!$res) {
			return null;
		}

		$row = $res->fetch(false);
		return is_array($row) ? $row[0] : null;
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		$this->query($savepoint ? "SAVEPOINT $savepoint" : 'START TRANSACTION');
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		$this->query($savepoint ? "RELEASE SAVEPOINT $savepoint" : 'COMMIT');
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		$this->query($savepoint ? "ROLLBACK TO SAVEPOINT $savepoint" : 'ROLLBACK');
	}


	/**
	 * Is in transaction?
	 */
	public function inTransaction(): bool
	{
		return !in_array(pg_transaction_status($this->connection), [PGSQL_TRANSACTION_UNKNOWN, PGSQL_TRANSACTION_IDLE], true);
	}


	/**
	 * Returns the connection resource.
	 * @return resource|null
	 */
	public function getResource()
	{
		return is_resource($this->connection) ? $this->connection : null;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Dibi\Reflector
	{
		return $this;
	}


	/**
	 * Result set driver factory.
	 * @param  resource
	 */
	public function createResultDriver($resource): Dibi\ResultDriver
	{
		$res = clone $this;
		$res->resultSet = $resource;
		return $res;
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		if (!is_resource($this->connection)) {
			throw new Dibi\Exception('Lost connection to server.');
		}
		return "'" . pg_escape_string($this->connection, $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		if (!is_resource($this->connection)) {
			throw new Dibi\Exception('Lost connection to server.');
		}
		return "'" . pg_escape_bytea($this->connection, $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		// @see http://www.postgresql.org/docs/8.2/static/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
		return '"' . str_replace('"', '""', $value) . '"';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? 'TRUE' : 'FALSE';
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDate($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format("'Y-m-d'");
	}


	/**
	 * @param  \DateTimeInterface|string|int
	 */
	public function escapeDateTime($value): string
	{
		if (!$value instanceof \DateTimeInterface) {
			$value = new Dibi\DateTime($value);
		}
		return $value->format("'Y-m-d H:i:s.u'");
	}


	/**
	 * Encodes string for use in a LIKE statement.
	 */
	public function escapeLike(string $value, int $pos): string
	{
		$bs = pg_escape_string($this->connection, '\\'); // standard_conforming_strings = on/off
		$value = pg_escape_string($this->connection, $value);
		$value = strtr($value, ['%' => $bs . '%', '_' => $bs . '_', '\\' => '\\\\']);
		return ($pos <= 0 ? "'%" : "'") . $value . ($pos >= 0 ? "%'" : "'");
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return pg_unescape_bytea($value);
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit < 0 || $offset < 0) {
			throw new Dibi\NotSupportedException('Negative offset or limit.');
		}
		if ($limit !== null) {
			$sql .= ' LIMIT ' . $limit;
		}
		if ($offset) {
			$sql .= ' OFFSET ' . $offset;
		}
	}


	/********************* result set ****************d*g**/


	/**
	 * Automatically frees the resources allocated for this result set.
	 */
	public function __destruct()
	{
		$this->autoFree && $this->getResultResource() && $this->free();
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	public function getRowCount(): int
	{
		return pg_num_rows($this->resultSet);
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		return Helpers::false2Null(pg_fetch_array($this->resultSet, null, $assoc ? PGSQL_ASSOC : PGSQL_NUM));
	}


	/**
	 * Moves cursor position without fetching row.
	 */
	public function seek(int $row): bool
	{
		return pg_result_seek($this->resultSet, $row);
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		pg_free_result($this->resultSet);
		$this->resultSet = null;
	}


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = pg_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = [
				'name' => pg_field_name($this->resultSet, $i),
				'table' => pg_field_table($this->resultSet, $i),
				'nativetype' => pg_field_type($this->resultSet, $i),
			];
			$row['fullname'] = $row['table'] ? $row['table'] . '.' . $row['name'] : $row['name'];
			$columns[] = $row;
		}
		return $columns;
	}


	/**
	 * Returns the result set resource.
	 * @return resource|null
	 */
	public function getResultResource()
	{
		$this->autoFree = false;
		return is_resource($this->resultSet) ? $this->resultSet : null;
	}


	/********************* Dibi\Reflector ****************d*g**/


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$version = pg_parameter_status($this->getResource(), 'server_version');
		if ($version < 7.4) {
			throw new Dibi\DriverException('Reflection requires PostgreSQL 7.4 and newer.');
		}

		$query = "
			SELECT
				table_name AS name,
				CASE table_type
					WHEN 'VIEW' THEN 1
					ELSE 0
				END AS view
			FROM
				information_schema.tables
			WHERE
				table_schema = ANY (current_schemas(false))";

		if ($version >= 9.3) {
			$query .= '
				UNION ALL
				SELECT
					matviewname, 1
				FROM
					pg_matviews
				WHERE
					schemaname = ANY (current_schemas(false))';
		}

		$res = $this->query($query);
		$tables = pg_fetch_all($res->resultSet);
		return $tables ? $tables : [];
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$_table = $this->escapeText($this->escapeIdentifier($table));
		$res = $this->query("
			SELECT indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid AND pg_index.indisprimary
			WHERE pg_class.oid = $_table::regclass
		");
		$primary = (int) pg_fetch_object($res->resultSet)->indkey;

		$res = $this->query("
			SELECT *
			FROM information_schema.columns c
			JOIN pg_class ON pg_class.relname = c.table_name
			JOIN pg_namespace nsp ON nsp.oid = pg_class.relnamespace AND nsp.nspname = c.table_schema
			WHERE pg_class.oid = $_table::regclass
			ORDER BY c.ordinal_position
		");

		if (!$res->getRowCount()) {
			$res = $this->query("
				SELECT
					a.attname AS column_name,
					pg_type.typname AS udt_name,
					a.attlen AS numeric_precision,
					a.atttypmod-4 AS character_maximum_length,
					NOT a.attnotnull AS is_nullable,
					a.attnum AS ordinal_position,
					adef.adsrc AS column_default
				FROM
					pg_attribute a
					JOIN pg_type ON a.atttypid = pg_type.oid
					JOIN pg_class cls ON a.attrelid = cls.oid
					LEFT JOIN pg_attrdef adef ON adef.adnum = a.attnum AND adef.adrelid = a.attrelid
				WHERE
					cls.relkind IN ('r', 'v', 'mv')
					AND a.attrelid = $_table::regclass
					AND a.attnum > 0
					AND NOT a.attisdropped
				ORDER BY ordinal_position
			");
		}

		$columns = [];
		while ($row = $res->fetch(true)) {
			$size = (int) max($row['character_maximum_length'], $row['numeric_precision']);
			$columns[] = [
				'name' => $row['column_name'],
				'table' => $table,
				'nativetype' => strtoupper($row['udt_name']),
				'size' => $size > 0 ? $size : null,
				'nullable' => $row['is_nullable'] === 'YES' || $row['is_nullable'] === 't',
				'default' => $row['column_default'],
				'autoincrement' => (int) $row['ordinal_position'] === $primary && substr($row['column_default'], 0, 7) === 'nextval',
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
		$_table = $this->escapeText($this->escapeIdentifier($table));
		$res = $this->query("
			SELECT
				a.attnum AS ordinal_position,
				a.attname AS column_name
			FROM
				pg_attribute a
				JOIN pg_class cls ON a.attrelid = cls.oid
			WHERE
				a.attrelid = $_table::regclass
				AND a.attnum > 0
				AND NOT a.attisdropped
			ORDER BY ordinal_position
		");

		$columns = [];
		while ($row = $res->fetch(true)) {
			$columns[$row['ordinal_position']] = $row['column_name'];
		}

		$res = $this->query("
			SELECT pg_class2.relname, indisunique, indisprimary, indkey
			FROM pg_class
			LEFT JOIN pg_index on pg_class.oid = pg_index.indrelid
			INNER JOIN pg_class as pg_class2 on pg_class2.oid = pg_index.indexrelid
			WHERE pg_class.oid = $_table::regclass
		");

		$indexes = [];
		while ($row = $res->fetch(true)) {
			$indexes[$row['relname']]['name'] = $row['relname'];
			$indexes[$row['relname']]['unique'] = $row['indisunique'] === 't';
			$indexes[$row['relname']]['primary'] = $row['indisprimary'] === 't';
			foreach (explode(' ', $row['indkey']) as $index) {
				$indexes[$row['relname']]['columns'][] = $columns[$index];
			}
		}
		return array_values($indexes);
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		$_table = $this->escapeText($this->escapeIdentifier($table));

		$res = $this->query("
			SELECT
				c.conname AS name,
				lt.attname AS local,
				c.confrelid::regclass AS table,
				ft.attname AS foreign,

				CASE c.confupdtype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onUpdate\",

				CASE c.confdeltype
					WHEN 'a' THEN 'NO ACTION'
					WHEN 'r' THEN 'RESTRICT'
					WHEN 'c' THEN 'CASCADE'
					WHEN 'n' THEN 'SET NULL'
					WHEN 'd' THEN 'SET DEFAULT'
					ELSE 'UNKNOWN'
				END AS \"onDelete\",

				c.conkey,
				lt.attnum AS lnum,
				c.confkey,
				ft.attnum AS fnum
			FROM
				pg_constraint c
				JOIN pg_attribute lt ON c.conrelid = lt.attrelid AND lt.attnum = ANY (c.conkey)
				JOIN pg_attribute ft ON c.confrelid = ft.attrelid AND ft.attnum = ANY (c.confkey)
			WHERE
				c.contype = 'f'
				AND
				c.conrelid = $_table::regclass
		");

		$fKeys = $references = [];
		while ($row = $res->fetch(true)) {
			if (!isset($fKeys[$row['name']])) {
				$fKeys[$row['name']] = [
					'name' => $row['name'],
					'table' => $row['table'],
					'local' => [],
					'foreign' => [],
					'onUpdate' => $row['onUpdate'],
					'onDelete' => $row['onDelete'],
				];

				$l = explode(',', trim($row['conkey'], '{}'));
				$f = explode(',', trim($row['confkey'], '{}'));

				$references[$row['name']] = array_combine($l, $f);
			}

			if (isset($references[$row['name']][$row['lnum']]) && $references[$row['name']][$row['lnum']] === $row['fnum']) {
				$fKeys[$row['name']]['local'][] = $row['local'];
				$fKeys[$row['name']]['foreign'][] = $row['foreign'];
			}
		}

		return $fKeys;
	}
}
