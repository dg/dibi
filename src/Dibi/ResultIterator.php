<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibiphp.com)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * External result set iterator.
 */
class ResultIterator implements \Iterator, \Countable
{
	private Result $result;
	private mixed $row;
	private int $pointer = 0;


	public function __construct(Result $result)
	{
		$this->result = $result;
	}


	/**
	 * Rewinds the iterator to the first element.
	 */
	public function rewind(): void
	{
		$this->pointer = 0;
		$this->result->seek(0);
		$this->row = $this->result->fetch();
	}


	/**
	 * Returns the key of the current element.
	 */
	#[\ReturnTypeWillChange]
	public function key(): mixed
	{
		return $this->pointer;
	}


	/**
	 * Returns the current element.
	 */
	#[\ReturnTypeWillChange]
	public function current(): mixed
	{
		return $this->row;
	}


	/**
	 * Moves forward to next element.
	 */
	public function next(): void
	{
		$this->row = $this->result->fetch();
		$this->pointer++;
	}


	/**
	 * Checks if there is a current element after calls to rewind() or next().
	 */
	public function valid(): bool
	{
		return !empty($this->row);
	}


	/**
	 * Required by the Countable interface.
	 */
	public function count(): int
	{
		return $this->result->getRowCount();
	}
}
