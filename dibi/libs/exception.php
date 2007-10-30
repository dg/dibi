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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  (dibi license)
 * @category   Database
 * @package    Dibi
 * @link       http://php7.org/dibi/
 */



/**
 * dibi common exception
 *
 * @version $Revision$ $Date$
 */
class DibiException extends Exception
{
}


/**
 * database server exception
 */
class DibiDatabaseException extends DibiException
{
    /** @var string */
    private $sql;


    public function __construct($message = NULL, $code = 0, $sql = NULL)
    {
        parent::__construct($message);
        $this->sql = $sql;
        dibi::notify('exception', NULL, $this);
    }



    final public function getSql()
    {
        return $this->sql;
    }



    public function __toString()
    {
        $s = parent::__toString();
        if ($this->sql) {
            $s .= "\nSQL: " . $this->sql;
        }
        return $s;
    }

}
