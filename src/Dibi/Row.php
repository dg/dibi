<?php declare(strict_types=1);

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;

use function array_keys, count, str_starts_with;


/**
 * Result set single row.
 * @implements \ArrayAccess<int|string, mixed>
 * @implements \IteratorAggregate<int|string, mixed>
 */
#[\AllowDynamicProperties]
class Row implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/** @param  mixed[]  $arr */
	public function __construct(array $arr)
	{
		foreach ($arr as $k => $v) {
			$this->$k = $v;
		}
	}


	/** @return mixed[] */
	public function toArray(): array
	{
		return (array) $this;
	}


	/**
	 * Converts value to DateTime object.
	 */
	public function asDateTime(string $key, ?string $format = null): DateTime|string|null
	{
		$time = $this[$key];
		if (!$time instanceof DateTime) {
			if (!$time || str_starts_with((string) $time, '0000-00')) { // '', null, false, '0000-00-00', ...
				return null;
			}

			$time = new DateTime($time);
		}

		return $format === null ? $time : $time->format($format);
	}


	public function __get(string $key): mixed
	{
		$hint = Helpers::getSuggestion(array_keys((array) $this), $key);
		trigger_error("Attempt to read missing column '$key'" . ($hint ? ", did you mean '$hint'?" : '.'), E_USER_NOTICE);
		return null;
	}


	public function __isset(string $key): bool
	{
		return false;
	}


	/********************* interfaces ArrayAccess, Countable & IteratorAggregate ****************d*g**/


	final public function count(): int
	{
		return count((array) $this);
	}


	/** @return \ArrayIterator<int|string, mixed> */
	final public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator((array) $this);
	}


	final public function offsetSet(mixed $nm, mixed $val): void
	{
		$this->$nm = $val;
	}


	final public function offsetGet(mixed $nm): mixed
	{
		return $this->$nm;
	}


	final public function offsetExists(mixed $nm): bool
	{
		return isset($this->$nm);
	}


	final public function offsetUnset(mixed $nm): void
	{
		unset($this->$nm);
	}
}
