<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\PDO;

use Dibi;
use Dibi\Drivers;
use Dibi\Drivers\Engines;
use Dibi\Helpers;
use PDO;
use function sprintf;


/**
 * The driver for PDO.
 *
 * Driver options:
 *   - dsn => driver specific DSN
 *   - username (or user)
 *   - password (or pass)
 *   - options (array) => driver specific options {@see PDO::__construct}
 *   - resource (PDO) => existing connection
 */
class Connection implements Drivers\Connection
{
	private ?PDO $connection;
	private ?int $affectedRows;
	private string $driverName;


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('pdo')) {
			throw new Dibi\NotSupportedException("PHP extension 'pdo' is not loaded.");
		}

		$foo = &$config['dsn'];
		$foo = &$config['options'];
		Helpers::alias($config, 'resource', 'pdo');

		if ($config['resource'] instanceof PDO) {
			$this->connection = $config['resource'];
			unset($config['resource'], $config['pdo']);

			if ($this->connection->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_SILENT) {
				throw new Dibi\DriverException('PDO connection in exception or warning error mode is not supported.');
			}
		} else {
			try {
				$this->connection = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
			} catch (\PDOException $e) {
				if ($e->getMessage() === 'could not find driver') {
					throw new Dibi\NotSupportedException('PHP extension for PDO is not loaded.');
				}

				throw new Dibi\DriverException($e->getMessage(), $e->getCode());
			}
		}

		$this->driverName = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
	}


	/**
	 * Disconnects from a database.
	 */
	public function disconnect(): void
	{
		$this->connection = null;
	}


	/**
	 * Executes the SQL query.
	 * @throws Dibi\DriverException
	 */
	public function query(string $sql): ?Result
	{
		$res = $this->connection->query($sql);
		if ($res) {
			$this->affectedRows = $res->rowCount();
			return $res->columnCount() ? $this->createResultDriver($res) : null;
		}

		$this->affectedRows = null;

		[$sqlState, $code, $message] = $this->connection->errorInfo();
		$code ??= 0;
		$message = "SQLSTATE[$sqlState]: $message";
		throw match ($this->driverName) {
			'mysql' => Drivers\MySQLi\Connection::createException($message, $code, $sql),
			'oci' => Drivers\OCI8\Connection::createException($message, $code, $sql),
			'pgsql' => Drivers\PgSQL\Connection::createException($message, $sqlState, $sql),
			'sqlite' => Drivers\SQLite3\Connection::createException($message, $code, $sql),
			default => new Dibi\DriverException($message, $code, $sql),
		};
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
		return Helpers::intVal($this->connection->lastInsertId($sequence));
	}


	/**
	 * Begins a transaction (if supported).
	 * @throws Dibi\DriverException
	 */
	public function begin(?string $savepoint = null): void
	{
		if (!$this->connection->beginTransaction()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1] ?? 0);
		}
	}


	/**
	 * Commits statements in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function commit(?string $savepoint = null): void
	{
		if (!$this->connection->commit()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1] ?? 0);
		}
	}


	/**
	 * Rollback changes in a transaction.
	 * @throws Dibi\DriverException
	 */
	public function rollback(?string $savepoint = null): void
	{
		if (!$this->connection->rollBack()) {
			$err = $this->connection->errorInfo();
			throw new Dibi\DriverException("SQLSTATE[$err[0]]: $err[2]", $err[1] ?? 0);
		}
	}


	/**
	 * Returns the connection resource.
	 */
	public function getResource(): ?PDO
	{
		return $this->connection;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Drivers\Engine
	{
		return match ($this->driverName) {
			'mysql' => new Engines\MySQLEngine($this),
			'oci' => new Engines\OracleEngine($this),
			'pgsql' => new Engines\PostgreSQLEngine($this),
			'sqlite' => new Engines\SQLiteEngine($this),
			'mssql', 'dblib', 'sqlsrv' => new Engines\SQLServerEngine($this),
			default => throw new Dibi\NotSupportedException,
		};
	}


	/**
	 * Result set driver factory.
	 */
	public function createResultDriver(\PDOStatement $result): Result
	{
		return new Result($result, $this->driverName);
	}


	/********************* SQL ****************d*g**/


	/**
	 * Encodes data for use in a SQL statement.
	 */
	public function escapeText(string $value): string
	{
		return match ($this->driverName) {
			'odbc' => "'" . str_replace("'", "''", $value) . "'",
			'sqlsrv' => "N'" . str_replace("'", "''", $value) . "'",
			default => $this->connection->quote($value, PDO::PARAM_STR),
		};
	}


	public function escapeBinary(string $value): string
	{
		return match ($this->driverName) {
			'odbc' => "'" . str_replace("'", "''", $value) . "'",
			'sqlsrv' => '0x' . bin2hex($value),
			default => $this->connection->quote($value, PDO::PARAM_LOB),
		};
	}
}
