<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * External result set iterator.
 *
 * This can be returned by Result::getIterator() method or using foreach
 * <code>
 * $result = dibi::query('SELECT * FROM table');
 * foreach ($result as $row) {
 *    print_r($row);
 * }
 * unset($result);
 * </code>
 */
class ResultIterator implements \Iterator, \Countable
{
	use Strict;

	/** @var Result */
	private $result;

	/** @var mixed */
	private $row;

	/** @var int */
	private $pointer = 0;


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
	 * @return mixed
	 */
	public function key()
	{
		return $this->pointer;
	}


	/**
	 * Returns the current element.
	 * @return mixed
	 */
	public function current()
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
