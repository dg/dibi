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
 * @link       http://dibi.texy.info/
 * @copyright  Copyright (c) 2005-2007 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    dibi
 * @category   Database
 * @version    0.7f $Revision$ $Date$
 */


define('DIBI', 'Version 0.7f $Revision$');


if (version_compare(PHP_VERSION , '5.0.3', '<'))
    die('dibi needs PHP 5.0.3 or newer');


// libraries
require_once dirname(__FILE__).'/libs/driver.php';
require_once dirname(__FILE__).'/libs/resultset.php';
require_once dirname(__FILE__).'/libs/translator.php';
require_once dirname(__FILE__).'/libs/exception.php';





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
 * store connections info.
 *
 */
class dibi
{
    /**
     * Column type in relation to PHP native type
     */
    const
        FIELD_TEXT =       's', // as 'string'
        FIELD_BINARY =     'S',
        FIELD_BOOL =       'b',
        FIELD_INTEGER =    'i',
        FIELD_FLOAT =      'f',
        FIELD_DATE =       'd',
        FIELD_DATETIME =   't',
        FIELD_UNKNOWN =    '?',

        // special
        FIELD_COUNTER =    'c'; // counter or autoincrement, is integer


    /**
     * Connection registry storage for DibiDriver objects
     * @var DibiDriver[]
     */
    static private $registry = array();

    /**
     * Current connection
     * @var DibiDriver
     */
    static private $conn;

    /**
     * Last SQL command @see dibi::query()
     * @var string
     */
    static public $sql;

    /**
     * File for logging SQL queries
     * @var string|NULL
     */
    static public $logFile;

    /**
     * Mode parameter used by fopen()
     * @var string
     */
    static public $logMode = 'a';

    /**
     * To log all queries or error queries (debug mode)
     * @var bool
     */
    static public $logAll = FALSE;

    /**
     * dibi::query() error mode
     * @var bool
     */
    static public $throwExceptions = FALSE;

    /**
     * Substitutions for identifiers
     * @var array
     */
    static private $substs = array();



    /**
     * Monostate class
     */
    private function __construct()
    {}


    /**
     * Creates a new DibiDriver object and connects it to specified database
     *
     * @param  array|string connection parameters
     * @param  string       connection name
     * @return void
     * @throw  DibiException
     */
    static public function connect($config, $name = '1')
    {
        // DSN string
        if (is_string($config))
            parse_str($config, $config);

        // config['driver'] is required
        if (empty($config['driver']))
            throw new DibiException('Driver is not specified.');

        // include dibi driver
        $className = "Dibi$config[driver]Driver";
        if (!class_exists($className)) {
            include_once dirname(__FILE__) . "/drivers/$config[driver].php";

            if (!class_exists($className))
                throw new DibiException("Unable to create instance of dibi driver class '$className'.");
        }


        // create connection object and store in list
        /** like $conn = $className::connect($config); */
        self::$conn = self::$registry[$name] = call_user_func(array($className, 'connect'), $config);

        if (dibi::$logAll) dibi::log("OK: connected to DB '$config[driver]'");
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
     * @return object   DibiDriver object.
     * @throw  DibiException
     */
    static public function getConnection()
    {
        if (!self::$conn)
            throw new DibiException('Dibi is not connected to database');

        return self::$conn;
    }



    /**
     * Change active connection
     *
     * @param  string   connection registy name
     * @return void
     * @throw  DibiException
     */
    static public function activate($name)
    {
        if (!isset(self::$registry[$name]))
            throw new DibiException("There is no connection named '$name'.");

        // change active connection
        self::$conn = self::$registry[$name];
    }



    /**
     * Generates and executes SQL query
     *
     * @param  array|mixed    one or more arguments
     * @return int|DibiResult
     * @throw  DibiException
     */
    static public function query($args)
    {
        $args = func_get_args();
        return self::getConnection()->query($args);
    }



    /**
     * Generates and prints SQL query
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    static public function test($args)
    {
        // receive arguments
        if (!is_array($args))
            $args = func_get_args();

        // and generate SQL
        $trans = new DibiTranslator(self::getConnection());
        try {
            $sql = $trans->translate($args);
        } catch (DibiException $e) {
            return FALSE;
        }

        if ($sql === FALSE) return FALSE;

        self::dump($sql);

        return TRUE;
    }



    /**
     * Monostate for DibiDriver::insertId()
     *
     * @return int
     */
    static public function insertId()
    {
        return self::getConnection()->insertId();
    }



    /**
     * Monostate for DibiDriver::affectedRows()
     *
     * @return int
     */
    static public function affectedRows()
    {
        return self::getConnection()->affectedRows();
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
     * @param bool   return or print?
     * @return void
     */
    static public function dump($sql, $return=FALSE) {
        static $keywords2 = 'ALL|DISTINCT|AS|ON|INTO|AND|OR|AS';
        static $keywords1 = 'SELECT|UPDATE|INSERT|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';

        // insert new lines
        $sql = preg_replace("#\\b(?:$keywords1)\\b#", "\n\$0", $sql);

        $sql = trim($sql);
        // reduce spaces
        $sql = preg_replace('# {2,}#', ' ', $sql);

        $sql = wordwrap($sql, 100);
        $sql = htmlSpecialChars($sql);
        $sql = preg_replace("#\n{2,}#", "\n", $sql);

        // syntax highlight
        $sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#", array('dibi', 'dumpHighlight'), $sql);
        $sql = '<pre class="dump">' . $sql . "</pre>\n";

        // print & return
        if (!$return) echo $sql;
        return $sql;
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
        echo '<th>#row</th>';
        foreach ($res->getFields() as $field)
            echo '<th>' . $field . '</th>';
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



    /**
     * Create a new substitution pair for indentifiers
     * @param string from
     * @param string to
     * @return void
     */
    static public function addSubst($expr, $subst)
    {
        self::$substs[':'.$expr.':'] = $subst;
    }


    /**
     * Remove substitution pair
     * @param string from
     * @return void
     */
    static public function removeSubst($expr)
    {
        unset(self::$substs[':'.$expr.':']);
    }


    /**
     * Process substitutions in string
     * @param string
     * @return string
     */
    static public function substitute($s)
    {
        if (strpos($s, ':') === FALSE) return $s;
        return strtr($s, self::$substs);
    }


    /**
     * Error logging
     * EXPERIMENTAL
     */
    static public function log($message)
    {
        if (self::$logFile == NULL || self::$logMode == NULL) return;

        $f = fopen(self::$logFile, self::$logMode);
        if (!$f) return;
        flock($f, LOCK_EX);
        fwrite($f, $message. "\n\n");
        fclose($f);
    }


} // class dibi
