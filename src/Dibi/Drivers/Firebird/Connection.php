<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Drivers\Ibase;

use Dibi;
use Dibi\Drivers;
use Dibi\Helpers;
use function is_resource;


/**
 * The driver for Firebird/InterBase database.
 *
 * Driver options:
 *   - database => the path to database file (server:/path/database.fdb)
 *   - username (or user)
 *   - password (or pass)
 *   - charset => character encoding to set
 *   - buffers (int) => buffers is the number of database buffers to allocate for the server-side cache. If 0 or omitted, server chooses its own default.
 *   - resource (resource) => existing connection resource
 */
class Connection implements Drivers\Connection
{
	public const ErrorExceptionThrown = -836;

	#[\Deprecated('use FirebirdDriver::ErrorExceptionThrown')]
	public const ERROR_EXCEPTION_THROWN = self::ErrorExceptionThrown;

	/** @var resource */
	private $connection;

	/** @var ?resource */
	private $transaction;
	private bool $inTransaction = false;


	/** @throws Dibi\NotSupportedException */
	public function __construct(array $config)
	{
		if (!extension_loaded('interbase')) {
			throw new Dibi\NotSupportedException("PHP extension 'interbase' is not loaded.");
		}

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

			$this->connection = empty($config['persistent'])
				? @ibase_connect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']) // intentionally @
				: @ibase_pconnect($config['database'], $config['username'], $config['password'], $config['charset'], $config['buffers']); // intentionally @

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
	public function query(string $sql): ?Result
	{
		$resource = $this->inTransaction
			? $this->transaction
			: $this->connection;
		$res = ibase_query($resource, $sql);

		if ($res === false) {
			if (ibase_errcode() === self::ERROR_EXCEPTION_THROWN) {
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
	public function begin(?string $savepoint = null): void
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
	public function commit(?string $savepoint = null): void
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
	public function rollback(?string $savepoint = null): void
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
	public function getResource(): mixed
	{
		return is_resource($this->connection) ? $this->connection : null;
	}


	/**
	 * Returns the connection reflector.
	 */
	public function getReflector(): Drivers\Engine
	{
		return new Drivers\Engines\FirebirdEngine($this);
	}


	/**
	 * Result set driver factory.
	 * @param  resource  $resource
	 */
	public function createResultDriver($resource): Result
	{
		return new Result($resource);
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
}
