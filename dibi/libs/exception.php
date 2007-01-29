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
        $info;


    public function __construct($message, $info=NULL) {

        $this->info = $info;

        if (isset($info['message']))
            $message = "$message: $info[message]";

        parent::__construct($message);
    }


    public function getSql()
    {
        return isset($this->info['sql']) ? $this->info['sql'] : NULL;
    }


    public function __toString()
    {
        $s = parent::__toString();
        if (isset($this->info['sql']))
            $s .= "\nSQL: " . $this->info['sql'];
        return $s;
    }

} // class DibiException
