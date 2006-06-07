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
 * @license    GNU GENERAL PUBLIC LICENSE
 * @package    dibi
 * @category   Database
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();


// required since PHP 5.1.0
// todo:
if (function_exists('date_default_timezone_set'))
    date_default_timezone_set('Europe/Prague');    // or 'GMT'



/**
 * Pseudotype for UNIX timestamp representation
 */
class TDate implements IDibiVariable
{
    /**
     * Unix timestamp
     * @var int
     */
    protected $time;



    public function __construct($time = NULL)
    {
        if ($time === NULL)
            $this->time = time(); // current time

        elseif (is_string($time))
            $this->time = strtotime($time); // try convert to timestamp

        else
            $this->time = (int) $time;
    }



    /**
     * Format for SQL
     *
     * @param  object  destination DibiDriver
     * @param  string  optional modifier
     * @return string
     */
    public function toSQL($driver, $modifier = NULL)
    {
        return date(
            $driver->formats['date'],  // format according to driver's spec.
            $this->time
        );
    }



    public function getTimeStamp()
    {
        return $this->time;
    }


}




/**
 * Pseudotype for datetime representation
 */
class TDateTime extends TDate
{

    public function toSQL($driver, $modifier = NULL)
    {
        return date(
            $driver->formats['datetime'],  // format according to driver's spec.
            $this->time
        );
    }

}

?>