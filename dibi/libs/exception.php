<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/dibi/
 * @copyright  Copyright (c) 2005-2006 David Grudl
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
/*
        if (isset($info['sql']))
            $message .= "\n[SQL] $info[sql]";
*/
        parent::__construct($message);
    }



    public function getSql()
    {
        return $this->info['sql'];
    }


} // class DibiException





/**
 * Checks result state
 */
function is_error($var)
{
    return ($var === FALSE) || ($var instanceof Exception);
}
