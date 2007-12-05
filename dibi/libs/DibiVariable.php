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
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * Interface for user variable, used for generating SQL
 * @package dibi
 */
interface DibiVariableInterface
{
    /**
     * Format for SQL
     *
     * @param  object  destination DibiDriverInterface
     * @param  string  optional modifier
     * @return string  SQL code
     */
    public function toSql(DibiDriverInterface $driver, $modifier);
}





class DibiVariable extends NObject implements DibiVariableInterface
{
    /** @var mixed */
    public $value;

    /** @var string */
    public $type;


    public function __construct($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
    }


    public function toSql(DibiDriverInterface $driver, $modifier)
    {
        return $driver->format($this->value, $this->type);
    }

}