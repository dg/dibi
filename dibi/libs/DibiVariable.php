<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * Default implemenation of IDibiVariable
 * @package dibi
 */
class DibiVariable extends NObject implements IDibiVariable
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


    public function toSql(IDibiDriver $driver, $modifier)
    {
        return $driver->format($this->value, $this->type);
    }

}