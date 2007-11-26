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
 * dibi common exception
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiException extends NException
{
}




/**
 * database server exception
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiDriverException extends DibiException
{
    /** @var string */
    private $sql;


    /**
     * Construct an dibi driver exception
	 *
	 * @param string  Message describing the exception
	 * @param int     Some code
     * @param string  SQL command
	 */
    public function __construct($message = NULL, $code = 0, $sql = NULL)
    {
        parent::__construct($message, (int) $code);
        $this->sql = $sql;
        dibi::notify(NULL, 'exception', $this);
    }



    /**
     * @return string  The SQL passed to the constructor
	 */
    final public function getSql()
    {
        return $this->sql;
    }



    /**
     * @return string  string represenation of exception with SQL command
     */
    public function __toString()
    {
        return parent::__toString() . ($this->sql ? "\nSQL: " . $this->sql : '');
    }



    /**
     * @see NException::catchError (this is Late static binding fix
     */
    public static function catchError($class = __CLASS__)
    {
        parent::catchError($class);
    }

}