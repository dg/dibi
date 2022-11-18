<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Profiler & logger event.
 */
class Event
{
	/** event type */
	public const
		CONNECT = 1,
		SELECT = 4,
		INSERT = 8,
		DELETE = 16,
		UPDATE = 32,
		QUERY = 60, // SELECT | INSERT | DELETE | UPDATE
		BEGIN = 64,
		COMMIT = 128,
		ROLLBACK = 256,
		TRANSACTION = 448, // BEGIN | COMMIT | ROLLBACK
		ALL = 1023;

	public Connection $connection;
	public int $type;
	public string $sql;
	public Result|DriverException|null $result;
	public float $time;
	public ?int $count = null;
	public ?array $source = null;


	public function __construct(Connection $connection, int $type, ?string $sql = null)
	{
		$this->connection = $connection;
		$this->type = $type;
		$this->sql = trim((string) $sql);
		$this->time = -microtime(true);

		if ($type === self::QUERY && preg_match('#\(?\s*(SELECT|UPDATE|INSERT|DELETE)#iA', $this->sql, $matches)) {
			$types = [
				'SELECT' => self::SELECT, 'UPDATE' => self::UPDATE,
				'INSERT' => self::INSERT, 'DELETE' => self::DELETE,
			];
			$this->type = $types[strtoupper($matches[1])];
		}

		$dibiDir = dirname((new \ReflectionClass('dibi'))->getFileName()) . DIRECTORY_SEPARATOR;
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $row) {
			if (
				isset($row['file'])
				&& preg_match('~\.(php.?|phtml)$~', $row['file'])
				&& !str_starts_with($row['file'], $dibiDir)
			) {
				$this->source = [$row['file'], (int) $row['line']];
				break;
			}
		}

		\dibi::$elapsedTime = null;
		\dibi::$numOfQueries++;
		\dibi::$sql = $sql;
	}


	public function done(Result|DriverException|null $result = null): static
	{
		$this->result = $result;
		try {
			$this->count = $result instanceof Result ? count($result) : null;
		} catch (Exception $e) {
			$this->count = null;
		}

		$this->time += microtime(true);
		\dibi::$elapsedTime = $this->time;
		\dibi::$totalTime += $this->time;
		return $this;
	}
}
