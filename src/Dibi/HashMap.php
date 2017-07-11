<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Lazy cached storage.
 * @internal
 */
abstract class HashMapBase
{
	private $callback;


	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}


	public function setCallback(callable $callback)
	{
		$this->callback = $callback;
	}


	public function getCallback()
	{
		return $this->callback;
	}
}


/**
 * Lazy cached storage.
 *
 * @internal
 */
final class HashMap extends HashMapBase
{
	public function __set($nm, $val)
	{
		if ($nm == '') {
			$nm = "\xFF";
		}
		$this->$nm = $val;
	}


	public function __get($nm)
	{
		if ($nm == '') {
			$nm = "\xFF";
			return $this->$nm = $this->$nm ?? $this->getCallback()('');
		} else {
			return $this->$nm = $this->getCallback()($nm);
		}
	}
}
