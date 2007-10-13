<?php

/**
 * This file is part of the "dibi" project (http://php7.org/dibi/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    New BSD License
 * @version    $Revision$ $Date$
 * @category   Database
 * @package    Dibi
 */



/**
 * dibi common exception
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
