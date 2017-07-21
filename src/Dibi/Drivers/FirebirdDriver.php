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
 * The dibi driver for Firebird/InterBase database.
 *
 * Driver options:
 *   - database => the path to database file (server:/path/database.fdb)
 *   - username (or user)
 *   - password (or pass)
 *   - charset => character encoding to set
 *   - buffers (int) => buffers is the number of database buffers to allocate for the server-side cache. If 0 or omitted, server chooses its own default.
 *   - resource (resource) => existing connection resource
 *   - lazy, profiler, result, substitutes, ... => see Dibi\Connection options
 */
class FirebirdDriver implements Dibi\Driver, Dibi\ResultDriver, Dibi\Reflector
{
	use Dibi\Strict;

	public const ERROR_EXCEPTION_THROWN = -836;

	/** @var resource|null */
	private $connection;

	/** @var resource|null */
	private $resultSet;

	/** @var bool */
	private $autoFree = true;

	/** @var resource|null */
	private $transaction;

	/** @var bool */
	private $inTransaction = false;


	/**
	 * @throws Dibi\NotSupportedException
	 */
	public function __construct()
	{
		if (!extension_loaded('interbase')) {
			throw new Dibi\NotSupportedException("PHP extension 'interbase' is not loaded.");
		}
	}


	/**
	 * Connects to a database.
	 * @throws Dibi\Exception
	 */
	public function connect(array &$config): void
	{
		Helpers::alias($config, 'database', 'db');

		if (isset($config['resource'])) {
			$this->connection = $config['resource'];

		} else {
			// default values
			$config += [
				'username' => ini_get('ibase.default_password'),
				'password' => ini_get('ibase.default_user'),
				'database' => ini_get('ibase.default_db'),
				'charset' => ini_get('ibase.default_charset'),
				'buffers' => 0,
			];

			if (empty($config['persistent'])) {
				$this->connection = @ibase_connect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']); // intentionally @
			} else {
				$this->connection = @ibase_pconnect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']); // intentionally @
			}

			if (!is_resource($this->connection)) {
				throw new Dibi\DriverException(ibase_errmsg(), ibase_errcode());
			}
		}
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		@ibase_close($this->connection); // @ - connection can be already disconnected
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException|Dibi\Exception
	 */
	public function query(string $sql): ?Dibi\ResultDriver
	{
		$resource = $this->inTransaction ? $this->transaction : $this->connection;
		$res = ibase_query($resource, $sql);

		if ($res === false) {
			if (ibase_errcode() == self::ERROR_EXCEPTION_THROWN) {
				preg_match('/exception (\d+) (\w+) (.*)/i', ibase_errmsg(), $match);
				throw new Dibi\ProcedureException($match[3], $match[1], $match[2], $sql);

			} else {
				throw new Dibi\DriverException(ibase_errmsg(), ibase_errcode(), $sql);
			}

		} elseif (is_resource($res)) {
			return $this->createResultDriver($res);
		}
		return null;
	}


	/**
	 * Gets the number of affected rows by the last INSERT, UPDATE or DELETE query.
	 */
	public function getAffectedRows(): ?int
	{
		return Helpers::false2Null(ibase_affected_rows($this->connection));
	}


	/**
	 * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query.
	 */
	public function getInsertId(?string $sequence): ?int
	{
		return Helpers::false2Null(ibase_gen_id($sequence, 0, $this->connection));
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(string $savepoint = null): void
	{
		if ($savepoint !== null) {
			throw new Dibi\NotSupportedException('Savepoints are not supported in Firebird/Interbase.');
		}
		$this->transaction = ibase_trans($this->getResource());
		$this->inTransaction = true;
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(string $savepoint = null): void
	{
		if ($savepoint !== null) {
			throw new Dibi\NotSupportedException('Savepoints are not supported in Firebird/Interbase.');
		}

		if (!ibase_commit($this->transaction)) {
			throw new Dibi\DriverException('Unable to handle operation - failure when commiting transaction.');
		}

		$this->inTransaction = false;
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(string $savepoint = null): void
	{
		if ($savepoint !== null) {
			throw new Dibi\NotSupportedException('Savepoints are not supported in Firebird/Interbase.');
		}

		if (!ibase_rollback($this->transaction)) {
			throw new Dibi\DriverException('Unable to handle operation - failure when rolbacking transaction.');
		}

		$this->inTransaction = false;
	}


	/**
	 * Is in transaction?
	 */
	public function inTransaction(): bool
	{
		return $this->inTransaction;
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


	/********************* SQL ********************/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeBinary(string $value): string
	{
		return "'" . str_replace("'", "''", $value) . "'";
	}


	public function escapeIdentifier(string $value): string
	{
		return '"' . str_replace('"', '""', $value) . '"';
	}


	public function escapeBool(bool $value): string
	{
		return $value ? '1' : '0';
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
		throw new Dibi\NotImplementedException;
	}


	/**
	 * Decodes data from result set.
	 */
	public function unescapeBinary(string $value): string
	{
		return $value;
	}


	/**
	 * Injects LIMIT/OFFSET to the SQL query.
	 */
	public function applyLimit(string &$sql, ?int $limit, ?int $offset): void
	{
		if ($limit > 0 || $offset > 0) {
			// http://www.firebirdsql.org/refdocs/langrefupd20-select.html
			$sql = 'SELECT ' . ($limit > 0 ? 'FIRST ' . $limit : '') . ($offset > 0 ? ' SKIP ' . $offset : '') . ' * FROM (' . $sql . ')';
		}
	}


	/********************* result set ********************/


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
		throw new Dibi\NotSupportedException('Firebird/Interbase do not support returning number of rows in result set.');
	}


	/**
	 * Fetches the row at current position and moves the internal cursor to the next position.
	 * @param  bool     true for associative array, false for numeric
	 */
	public function fetch(bool $assoc): ?array
	{
		$result = $assoc ? @ibase_fetch_assoc($this->resultSet, IBASE_TEXT) : @ibase_fetch_row($this->resultSet, IBASE_TEXT); // intentionally @

		if (ibase_errcode()) {
			if (ibase_errcode() == self::ERROR_EXCEPTION_THROWN) {
				preg_match('/exception (\d+) (\w+) (.*)/is', ibase_errmsg(), $match);
				throw new Dibi\ProcedureException($match[3], $match[1], $match[2]);

			} else {
				throw new Dibi\DriverException(ibase_errmsg(), ibase_errcode());
			}
		}

		return Helpers::false2Null($result);
	}


	/**
	 * Moves cursor position without fetching row.
	 * @throws Dibi\Exception
	 */
	public function seek(int $row): bool
	{
		throw new Dibi\NotSupportedException('Firebird/Interbase do not support seek in result set.');
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	public function free(): void
	{
		ibase_free_result($this->resultSet);
		$this->resultSet = null;
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


	/**
	 * Returns metadata for all columns in a result set.
	 */
	public function getResultColumns(): array
	{
		$count = ibase_num_fields($this->resultSet);
		$columns = [];
		for ($i = 0; $i < $count; $i++) {
			$row = (array) ibase_field_info($this->resultSet, $i);
			$columns[] = [
				'name' => $row['name'],
				'fullname' => $row['name'],
				'table' => $row['relation'],
				'nativetype' => $row['type'],
			];
		}
		return $columns;
	}


	/********************* Dibi\Reflector ********************/


	/**
	 * Returns list of tables.
	 */
	public function getTables(): array
	{
		$res = $this->query("
			SELECT TRIM(RDB\$RELATION_NAME),
				CASE RDB\$VIEW_BLR WHEN NULL THEN 'TRUE' ELSE 'FALSE' END
			FROM RDB\$RELATIONS
			WHERE RDB\$SYSTEM_FLAG = 0;"
		);
		$tables = [];
		while ($row = $res->fetch(false)) {
			$tables[] = [
				'name' => $row[0],
				'view' => $row[1] === 'TRUE',
			];
		}
		return $tables;
	}


	/**
	 * Returns metadata for all columns in a table.
	 */
	public function getColumns(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->query("
			SELECT TRIM(r.RDB\$FIELD_NAME) AS FIELD_NAME,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				r.RDB\$DEFAULT_VALUE AS DEFAULT_VALUE,
				CASE r.RDB\$NULL_FLAG
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS NULLABLE
			FROM RDB\$RELATION_FIELDS r
				LEFT JOIN RDB\$FIELDS f ON r.RDB\$FIELD_SOURCE = f.RDB\$FIELD_NAME
			WHERE r.RDB\$RELATION_NAME = '$table'
			ORDER BY r.RDB\$FIELD_POSITION;"

		);
		$columns = [];
		while ($row = $res->fetch(true)) {
			$key = $row['FIELD_NAME'];
			$columns[$key] = [
				'name' => $key,
				'table' => $table,
				'nativetype' => trim($row['FIELD_TYPE']),
				'size' => $row['FIELD_LENGTH'],
				'nullable' => $row['NULLABLE'] === 'TRUE',
				'default' => $row['DEFAULT_VALUE'],
				'autoincrement' => false,
			];
		}
		return $columns;
	}


	/**
	 * Returns metadata for all indexes in a table (the constraints are included).
	 */
	public function getIndexes(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
				i.RDB\$UNIQUE_FLAG AS UNIQUE_FLAG,
				i.RDB\$FOREIGN_KEY AS FOREIGN_KEY,
				TRIM(r.RDB\$CONSTRAINT_TYPE) AS CONSTRAINT_TYPE,
				s.RDB\$FIELD_POSITION AS FIELD_POSITION
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$INDICES i ON i.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
			ORDER BY s.RDB\$FIELD_POSITION"
		);
		$indexes = [];
		while ($row = $res->fetch(true)) {
			$key = $row['INDEX_NAME'];
			$indexes[$key]['name'] = $key;
			$indexes[$key]['unique'] = $row['UNIQUE_FLAG'] === 1;
			$indexes[$key]['primary'] = $row['CONSTRAINT_TYPE'] === 'PRIMARY KEY';
			$indexes[$key]['table'] = $table;
			$indexes[$key]['columns'][$row['FIELD_POSITION']] = $row['FIELD_NAME'];
		}
		return $indexes;
	}


	/**
	 * Returns metadata for all foreign keys in a table.
	 */
	public function getForeignKeys(string $table): array
	{
		$table = strtoupper($table);
		$res = $this->query("
			SELECT TRIM(s.RDB\$INDEX_NAME) AS INDEX_NAME,
				TRIM(s.RDB\$FIELD_NAME) AS FIELD_NAME,
			FROM RDB\$INDEX_SEGMENTS s
				LEFT JOIN RDB\$RELATION_CONSTRAINTS r ON r.RDB\$INDEX_NAME = s.RDB\$INDEX_NAME
			WHERE UPPER(i.RDB\$RELATION_NAME) = '$table'
				AND r.RDB\$CONSTRAINT_TYPE = 'FOREIGN KEY'
			ORDER BY s.RDB\$FIELD_POSITION"
		);
		$keys = [];
		while ($row = $res->fetch(true)) {
			$key = $row['INDEX_NAME'];
			$keys[$key] = [
				'name' => $key,
				'column' => $row['FIELD_NAME'],
				'table' => $table,
			];
		}
		return $keys;
	}


	/**
	 * Returns list of indices in given table (the constraints are not listed).
	 */
	public function getIndices(string $table): array
	{
		$res = $this->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND RDB\$UNIQUE_FLAG IS NULL
				AND RDB\$FOREIGN_KEY IS NULL;"
		);
		$indices = [];
		while ($row = $res->fetch(false)) {
			$indices[] = $row[0];
		}
		return $indices;
	}


	/**
	 * Returns list of constraints in given table.
	 */
	public function getConstraints(string $table): array
	{
		$res = $this->query("
			SELECT TRIM(RDB\$INDEX_NAME)
			FROM RDB\$INDICES
			WHERE RDB\$RELATION_NAME = UPPER('$table')
				AND (
					RDB\$UNIQUE_FLAG IS NOT NULL
					OR RDB\$FOREIGN_KEY IS NOT NULL
			);"
		);
		$constraints = [];
		while ($row = $res->fetch(false)) {
			$constraints[] = $row[0];
		}
		return $constraints;
	}


	/**
	 * Returns metadata for all triggers in a table or database.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 */
	public function getTriggersMeta(string $table = null): array
	{
		$res = $this->query("
			SELECT TRIM(RDB\$TRIGGER_NAME) AS TRIGGER_NAME,
				TRIM(RDB\$RELATION_NAME) AS TABLE_NAME,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'BEFORE'
					WHEN 2 THEN 'AFTER'
					WHEN 3 THEN 'BEFORE'
					WHEN 4 THEN 'AFTER'
					WHEN 5 THEN 'BEFORE'
					WHEN 6 THEN 'AFTER'
				END AS TRIGGER_TYPE,
				CASE RDB\$TRIGGER_TYPE
					WHEN 1 THEN 'INSERT'
					WHEN 2 THEN 'INSERT'
					WHEN 3 THEN 'UPDATE'
					WHEN 4 THEN 'UPDATE'
					WHEN 5 THEN 'DELETE'
					WHEN 6 THEN 'DELETE'
				END AS TRIGGER_EVENT,
				CASE RDB\$TRIGGER_INACTIVE
					WHEN 1 THEN 'FALSE' ELSE 'TRUE'
				END AS TRIGGER_ENABLED
			FROM RDB\$TRIGGERS
			WHERE RDB\$SYSTEM_FLAG = 0"
			. ($table === null ? ';' : " AND RDB\$RELATION_NAME = UPPER('$table');")
		);
		$triggers = [];
		while ($row = $res->fetch(true)) {
			$triggers[$row['TRIGGER_NAME']] = [
				'name' => $row['TRIGGER_NAME'],
				'table' => $row['TABLE_NAME'],
				'type' => trim($row['TRIGGER_TYPE']),
				'event' => trim($row['TRIGGER_EVENT']),
				'enabled' => trim($row['TRIGGER_ENABLED']) === 'TRUE',
			];
		}
		return $triggers;
	}


	/**
	 * Returns list of triggers for given table.
	 * (Only if user has permissions on ALTER TABLE, INSERT/UPDATE/DELETE record in table)
	 */
	public function getTriggers(string $table = null): array
	{
		$q = 'SELECT TRIM(RDB$TRIGGER_NAME)
			FROM RDB$TRIGGERS
			WHERE RDB$SYSTEM_FLAG = 0';
		$q .= $table === null ? ';' : " AND RDB\$RELATION_NAME = UPPER('$table')";

		$res = $this->query($q);
		$triggers = [];
		while ($row = $res->fetch(false)) {
			$triggers[] = $row[0];
		}
		return $triggers;
	}


	/**
	 * Returns metadata from stored procedures and their input and output parameters.
	 */
	public function getProceduresMeta(): array
	{
		$res = $this->query("
			SELECT
				TRIM(p.RDB\$PARAMETER_NAME) AS PARAMETER_NAME,
				TRIM(p.RDB\$PROCEDURE_NAME) AS PROCEDURE_NAME,
				CASE p.RDB\$PARAMETER_TYPE
					WHEN 0 THEN 'INPUT'
					WHEN 1 THEN 'OUTPUT'
					ELSE 'UNKNOWN'
				END AS PARAMETER_TYPE,
				CASE f.RDB\$FIELD_TYPE
					WHEN 261 THEN 'BLOB'
					WHEN 14 THEN 'CHAR'
					WHEN 40 THEN 'CSTRING'
					WHEN 11 THEN 'D_FLOAT'
					WHEN 27 THEN 'DOUBLE'
					WHEN 10 THEN 'FLOAT'
					WHEN 16 THEN 'INT64'
					WHEN 8 THEN 'INTEGER'
					WHEN 9 THEN 'QUAD'
					WHEN 7 THEN 'SMALLINT'
					WHEN 12 THEN 'DATE'
					WHEN 13 THEN 'TIME'
					WHEN 35 THEN 'TIMESTAMP'
					WHEN 37 THEN 'VARCHAR'
					ELSE 'UNKNOWN'
				END AS FIELD_TYPE,
				f.RDB\$FIELD_LENGTH AS FIELD_LENGTH,
				p.RDB\$PARAMETER_NUMBER AS PARAMETER_NUMBER
			FROM RDB\$PROCEDURE_PARAMETERS p
				LEFT JOIN RDB\$FIELDS f ON f.RDB\$FIELD_NAME = p.RDB\$FIELD_SOURCE
			ORDER BY p.RDB\$PARAMETER_TYPE, p.RDB\$PARAMETER_NUMBER;"
		);
		$procedures = [];
		while ($row = $res->fetch(true)) {
			$key = $row['PROCEDURE_NAME'];
			$io = trim($row['PARAMETER_TYPE']);
			$num = $row['PARAMETER_NUMBER'];
			$procedures[$key]['name'] = $row['PROCEDURE_NAME'];
			$procedures[$key]['params'][$io][$num]['name'] = $row['PARAMETER_NAME'];
			$procedures[$key]['params'][$io][$num]['type'] = trim($row['FIELD_TYPE']);
			$procedures[$key]['params'][$io][$num]['size'] = $row['FIELD_LENGTH'];
		}
		return $procedures;
	}


	/**
	 * Returns list of stored procedures.
	 */
	public function getProcedures(): array
	{
		$res = $this->query('
			SELECT TRIM(RDB$PROCEDURE_NAME)
			FROM RDB$PROCEDURES;'
		);
		$procedures = [];
		while ($row = $res->fetch(false)) {
			$procedures[] = $row[0];
		}
		return $procedures;
	}


	/**
	 * Returns list of generators.
	 */
	public function getGenerators(): array
	{
		$res = $this->query('
			SELECT TRIM(RDB$GENERATOR_NAME)
			FROM RDB$GENERATORS
			WHERE RDB$SYSTEM_FLAG = 0;'
		);
		$generators = [];
		while ($row = $res->fetch(false)) {
			$generators[] = $row[0];
		}
		return $generators;
	}


	/**
	 * Returns list of user defined functions (UDF).
	 */
	public function getFunctions(): array
	{
		$res = $this->query('
			SELECT TRIM(RDB$FUNCTION_NAME)
			FROM RDB$FUNCTIONS
			WHERE RDB$SYSTEM_FLAG = 0;'
		);
		$functions = [];
		while ($row = $res->fetch(false)) {
			$functions[] = $row[0];
		}
		return $functions;
	}
}
