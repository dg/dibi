<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi\Loggers;

use Dibi;


/**
 * Dibi file logger.
 */
class FileLogger
{
	use Dibi\Strict;

	/** @var string  Name of the file where SQL errors should be logged */
	public $file;

	/** @var int */
	public $filter;

	/** @var bool */
	private $errorsOnly;

	public function __construct(string $file, int $filter = null, bool $errorsOnly)
	{
		$this->file = $file;
		$this->filter = $filter ?: Dibi\Event::QUERY;
		$this->errorsOnly = $errorsOnly ?? false;
	}


	/**
	 * After event notification.
	 */
	public function logEvent(Dibi\Event $event): void
	{
		if (($event->type & $this->filter) === 0) {
			return;
		}

		if ($this->errorsOnly === true && ($event->result instanceof \Exception) === false) {
			return;
		}

		if ($event->result instanceof \Exception) {
			$message = $event->result->getMessage();
			if ($code = $event->result->getCode()) {
				$message = "[$code] $message";
			}
			$message = "ERROR: $message"
					.  "\n-- SQL: " . $event->sql;

			$this->writeToFile($event, $message);
			return;

		}

		$message = 'OK: ' . $event->sql
				 . ($event->count ? ";\n-- rows: " . $event->count : '')
				 . "\n-- takes: " . sprintf('%0.3f ms', $event->time * 1000)
				 . "\n-- source: " . implode(':', $event->source);

		$this->writeToFile($event, $message);
	}

	private function writeToFile(Dibi\Event $event, $message): void
	{
		if (is_writable(dirname($this->file)) === false) return;

		$message .= "\n-- driver: " . $event->connection->getConfig('driver') . '/' . $event->connection->getConfig('name')
				 .  "\n-- " . date('Y-m-d H:i:s')
				 .  "\n\n";

		file_put_contents($this->file, $message, FILE_APPEND | LOCK_EX);
	}
}
