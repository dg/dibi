<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Lazy cached storage.
 * @internal
 */
#[\AllowDynamicProperties]
abstract class HashMapBase
{
	private \Closure $callback;


	public function __construct(callable $callback)
	{
		$this->callback = $callback(...);
	}


	public function setCallback(callable $callback): void
	{
		$this->callback = $callback(...);
	}


	public function getCallback(): \Closure
	{
		return $this->callback;
	}
}


/**
 * Lazy cached storage.
 * @internal
 */
final class HashMap extends HashMapBase
{
	public function __set(string $nm, mixed $val): void
	{
		if ($nm === '') {
			$nm = "\xFF";
		}

		$this->$nm = $val;
	}


	public function __get(string $nm): mixed
	{
		if ($nm === '') {
			$nm = "\xFF";
			return isset($this->$nm) && true ? $this->$nm : $this->$nm = $this->getCallback()('');
		} else {
			return $this->$nm = $this->getCallback()($nm);
		}
	}
}
