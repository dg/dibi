<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://php7.org/dibi/
 *
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @link       http://php7.org/dibi/
 * @package    dibi
 */



/**
 * External result set iterator
 *
 * This can be returned by DibiResult::getIterator() method or using foreach
 * <code>
 * $result = dibi::query('SELECT * FROM table');
 * foreach ($result as $row) {
 *    print_r($row);
 * }
 * unset($result);
 * </code>
 *
 * Optionally you can specify offset and limit:
 * <code>
 * foreach ($result->getIterator(2, 3) as $row) {
 *     print_r($row);
 * }
 * </code>
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
final class DibiResultIterator implements Iterator
{
    /** @var DibiResult */
    private $result;

    /** @var int */
    private $offset;

    /** @var int */
    private $limit;

    /** @var int */
    private $row;

    /** @var int */
    private $pointer;


    /**
     * Required by the Iterator interface
     * @param int  offset
     * @param int  limit
     */
    public function __construct(DibiResult $result, $offset, $limit)
    {
        $this->result = $result;
        $this->offset = (int) $offset;
        $this->limit = $limit === NULL ? -1 : (int) $limit;
    }



    /**
     * Rewinds the Iterator to the first element
     * @return void
	 */
    public function rewind()
    {
        $this->pointer = 0;
        $this->result->seek($this->offset);
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
     * @return void
	 */
    public function next()
    {
        $this->row = $this->result->fetch();
        $this->pointer++;
    }



    /**
     * Checks if there is a current element after calls to rewind() or next().
     * @return bool
	 */
    public function valid()
    {
        return is_array($this->row) && ($this->limit < 0 || $this->pointer < $this->limit);
    }


}
