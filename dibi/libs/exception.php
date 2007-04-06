<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://dibi.texy.info/
 * @copyright  Copyright (c) 2005-2007 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();



/**
 * dibi exception class
 *
 */
class DibiException extends Exception
{
    private
        $sql,
        $dbError;


    public function __construct($message, $dbError=NULL, $sql=NULL)
    {
        $this->dbError = $dbError;
        $this->sql = $sql;

        parent::__construct($message);
    }


    public function getSql()
    {
        return $this->sql;
    }


    public function getDbError()
    {
        return $this->dbError;
    }


    public function __toString()
    {
        $s = parent::__toString();

        if ($this->dbError) {
            $s .= "\n\nDatabase error: ";
            if (isset($this->dbError['code']))
                $s .= "[" . $this->dbError['code'] . "] ";

            $s .= $this->dbError['message'];
        }

        if ($this->sql) $s .= "\nSQL: " . $this->sql;

        return $s;
    }

} // class DibiException
