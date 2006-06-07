<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * For PHP 5.0.3 or newer
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/dibi/
 * @copyright  Copyright (c) 2005-2006 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE
 * @package    dibi
 * @category   Database
 * @version    0.6 $Revision$ $Date$
 */


define('DIBI', 'Version 0.6 $Revision$');


if (version_compare(PHP_VERSION , '5.0.3', '<'))
    die('dibi needs PHP 5.0.3 or newer');


// libraries
require_once dirname(__FILE__).'/libs/driver.php';
require_once dirname(__FILE__).'/libs/resultset.php';
require_once dirname(__FILE__).'/libs/parser.php';
require_once dirname(__FILE__).'/libs/exception.php';



// required since PHP 5.1.0
if (function_exists('date_default_timezone_set'))
    date_default_timezone_set('Europe/Prague');    // or 'GMT'



/**
 * Interface for user variable, used for generating SQL
 */
interface IDibiVariable
{
    /**
     * Format for SQL
     *
     * @param  object  destination DibiDriver
     * @param  string  optional modifier
     * @return string  SQL code
     */
    public function toSQL($driver, $modifier = NULL);
}





/**
 * Interface for database drivers
 *
 * This class is static container class for creating DB objects and
 * store debug & connections info.
 *
 */
class dibi
{
    /**
     * Connection registry storage for DibiDriver objects
     * @var array
     */
    static private $registry = array();

    /**
     * Current connection
     * @var object DibiDriver
     */
    static private $conn;

    /**
     * Arguments -> SQL parser
     * @var object DibiParser
     */
    static private $parser;

    /**
     * Last SQL command @see dibi::query()
     * @var string
     */
    static public $sql;
    static public $error;

    /**
     * File for logging SQL queryies - strongly recommended to use with NSafeStream
     * @var string|NULL
     */
    static public $logfile;

    /**
     * Enable/disable debug mode
     * @var bool
     */
    static public $debug = false;

    /**
     * Progressive created query
     * @var array
     */
    static private $query = array();



    /**
     * Creates a new DibiDriver object and connects it to specified database
     *
     * @param  array        connection parameters
     * @param  string       connection name
     * @return bool|object  TRUE on success, FALSE or Exception on failure
     */
    static public function connect($config, $name = 'def')
    {
        // init parser
        if (!self::$parser) self::$parser = new DibiParser();

        // $name must be unique
        if (isset(self::$registry[$name]))
            return new DibiException("Connection named '$name' already exists.");

        // config['driver'] is required
        if (empty($config['driver']))
            return new DibiException('Driver is not specified.');

        // include dibi driver
        $className = "Dibi$config[driver]Driver";
        require_once dirname(__FILE__) . "/drivers/$config[driver].php";

        if (!class_exists($className))
            return new DibiException("Unable to create instance of dibi driver class '$className'.");


        // create connection object
        /** like $conn = $className::connect($config); */
        $conn = call_user_func(array($className, 'connect'), $config);

        // optionally log to file
        // todo: log other exceptions!
        if (self::$logfile != NULL) {
            if (is_error($conn))
                $msg = "Can't connect to DB '$config[driver]': ".$conn->getMessage();
            else
                $msg = "Successfully connected to DB '$config[driver]'";

            $f = fopen(self::$logfile, 'a');
            fwrite($f, "$msg\r\n\r\n");
            fclose($f);
        }

        if (is_error($conn)) {
            // optionally debug on display
            if (self::$debug) echo '[dibi error] ' . $conn->getMessage();

            return $conn; // reraise the exception
        }

        // store connection in list
        self::$conn = self::$registry[$name] = $conn;

        return TRUE;
    }



    /**
     * Returns TRUE when connection was established
     *
     * @return bool
     */
    static public function isConnected()
    {
        return (bool) self::$conn;
    }


    /**
     * Retrieve active connection
     *
     * @param  string   connection registy name or NULL for active connection
     * @return object   DibiDriver object.
     */
    static public function getConnection($name = NULL)
    {
        return $name === NULL
               ? self::$conn
               : @self::$registry[$name];
    }


    /**
     * Change active connection
     *
     * @param  string   connection registy name
     * @return void
     */
    static public function activate($name)
    {
        if (!isset(self::$registry[$name]))
            return FALSE;

        // change active connection
        self::$conn = self::$registry[$name];
        return TRUE;
    }






    /**
     * Generates and executes SQL query
     *
     * @param  mixed        one or more arguments
     * @return int|DibiResult|Exception
     */
    static public function query()
    {
        if (!self::$conn) return new DibiException('Dibi is not connected to DB'); // is connected?

        // receive arguments
        $args = func_num_args() ? func_get_args() : self::$query;
        self::$query = array();

        // and generate SQL
        self::$sql = self::$parser->parse(self::$conn, $args);
        if (is_error(self::$sql)) return self::$sql;  // reraise the exception

        // execute SQL
        $timer = -microtime(true);
        $res = self::$conn->query(self::$sql);
        $timer += microtime(true);

        if (is_error($res)) {
            // optionally debug on display
            if (self::$debug) {
                echo '[dibi error] ' . $res->getMessage();
                self::dump(self::$sql);
            }
            // todo: log all errors!
            self::$error = $res;
        } else {
            self::$error = FALSE;
        }

        // optionally log to file
        if (self::$logfile != NULL)
        {
            if (is_error($res))
                $msg = $res->getMessage();
            elseif ($res instanceof DibiResult)
                $msg = 'object('.get_class($res).') rows: '.$res->rowCount();
            else
                $msg = 'OK';

            $f = fopen(self::$logfile, 'a');
            fwrite($f,
               self::$sql
               . ";\r\n-- Result: $msg"
               . "\r\n-- Takes: " . sprintf('%0.3f', $timer * 1000) . ' ms'
               . "\r\n\r\n"
            );
            fclose($f);
        }

        return $res;
    }


/* CURRENTLY DISABLED - try conditional SQL %if ... %else ... %end
    static public function queryStart()
    {
        self::$query = func_get_args();
    }


    static public function queryAdd()
    {
        $args = func_get_args();
        self::$query = array_merge(self::$query, $args);
    }
*/


    /**
     * Generates and returns SQL query
     *
     * @param  mixed        one or more arguments
     * @return string
     */
    static public function test()
    {
        if (!self::$conn) return FALSE; // is connected?

        // receive arguments
        $args = func_num_args() ? func_get_args() : self::$query;
        self::$query = array();

        // and generate SQL
        $sql = self::$parser->parse(self::$conn, $args);
        if (is_error($sql)) {
            self::dump($sql->getSql());
            return $sql->getSql();
        } else {
            self::dump($sql);
            return $sql;
        }
    }



    /**
     * Monostate for DibiDriver::insertId()
     *
     * @return int
     */
    static public function insertId()
    {
        if (!self::$conn) return FALSE; // is connected?

        return self::$conn->insertId();
    }



    /**
     * Monostate for DibiDriver::affectedRows()
     *
     * @return int
     */
    static public function affectedRows()
    {
        if (!self::$conn) return FALSE; // is connected?

        return self::$conn->affectedRows();
    }



    static private function dumpHighlight($matches)
    {
        if (!empty($matches[1])) // comment
            return '<em style="color:gray">'.$matches[1].'</em>';

        if (!empty($matches[2])) // error
            return '<strong style="color:red">'.$matches[2].'</strong>';

        if (!empty($matches[3])) // most important keywords
            return '<strong style="color:blue">'.$matches[3].'</strong>';

        if (!empty($matches[4])) // other keywords
            return '<strong style="color:green">'.$matches[4].'</strong>';            
    }


    /**
     * Prints out a syntax highlighted version of the SQL command
     *
     * @param string   SQL command
     * @return void
     */
    static public function dump($sql) {
        static $keywords2 = 'ALL|DISTINCT|AS|ON|INTO|AND|OR|AS';
        static $keywords1 = 'SELECT|UPDATE|INSERT|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';

        // insert new lines
        $sql = preg_replace("#\\b(?:$keywords1)\\b#", "\n\$0", $sql);

        $sql = trim($sql);
        // reduce spaces
        // $sql = preg_replace('#  +#', ' ', $sql);

        $sql = wordwrap($sql, 100);
        $sql = htmlSpecialChars($sql);
        $sql = strtr($sql, array("\n" => '<br />'));

        // syntax highlight
        $sql = preg_replace_callback("#(/\*.+?\*/)|(\*\*.+?\*\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#", array('dibi', 'dumpHighlight'), $sql);

        echo '<pre class="dibi">', $sql, '</pre>';
    }



    /**
     * Displays complete result-set as HTML table
     *
     * @param object   DibiResult
     * @return void
     */
    static public function dumpResult(DibiResult $res)
    {
        echo '<table class="dump"><tr>';
        echo '<th>Row</th>';
        $fieldCount = $res->fieldCount();
        for ($i = 0; $i < $fieldCount; $i++) {
            $info = $res->fieldMeta($i);
            echo '<th>'.htmlSpecialChars($info['name']).'</th>';
        }
        echo '</tr>';

        foreach ($res as $row => $fields) {
            echo '<tr><th>', $row, '</th>';
            foreach ($fields as $field) {
                if (is_object($field)) $field = $field->__toString();
                echo '<td>', htmlSpecialChars($field), '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }



} // class dibi





?>