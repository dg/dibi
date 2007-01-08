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
 * @version    0.6e $Revision$ $Date$
 */


define('DIBI', 'Version 0.6e $Revision$');


if (version_compare(PHP_VERSION , '5.0.3', '<'))
    die('dibi needs PHP 5.0.3 or newer');


// libraries
require_once dirname(__FILE__).'/libs/driver.php';
require_once dirname(__FILE__).'/libs/resultset.php';
require_once dirname(__FILE__).'/libs/translator.php';
require_once dirname(__FILE__).'/libs/exception.php';



// required since PHP 5.1.0
// if (function_exists('date_default_timezone_set'))
//     date_default_timezone_set('Europe/Prague'); // or 'GMT'



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
     * Query rrror modes
     */
    const
        ERR_SILENT    = 1,
        ERR_WARNING   = 2,
        ERR_EXCEPTION = 3;


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
     * Last SQL command @see dibi::query()
     * @var string
     */
    static public $sql;
    static public $error;

    /**
     * File for logging SQL queryies - strongly recommended to use with NSafeStream
     * @var string|NULL
     */
    static public $logFile;
    static public $logMode = 'a';

    /**
     * Query error mode
     */
    static public $errorMode = dibi::ERR_SILENT;

    /**
     * Enable/disable debug mode
     * @var bool
     */
    static public $debug = false;


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
     * @throw DibiException
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

        if (dibi::$debug) dibi::log("Successfully connected to DB '$config[driver]'");
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
     * @throw DibiException
     */
    static public function query($args)
    {
        $conn = self::getConnection();

        // receive arguments
        if (!is_array($args))
            $args = func_get_args();

        // and generate SQL
        $trans = new DibiTranslator($conn, self::$substs);
        self::$sql = $trans->translate($args);

        // execute SQL
        $timer = -microtime(true);
        try {
            $res = $conn->query(self::$sql);
            self::$error = FALSE;

        } catch (DibiException $e) {
            $res = FALSE;
            self::$error = $e;
            if (dibi::$errorMode === self::ERR_WARNING) {
                trigger_error('[dibi] ' . $e->getMessage(), E_USER_WARNING);
            }
        }
        $timer += microtime(true);

        // optional log to file
        if (self::$logFile != NULL)
        {
            if (self::$error)
                $msg = self::$error->getMessage();
            elseif ($res instanceof DibiResult)
                $msg = 'object('.get_class($res).') rows: '.$res->rowCount();
            else
                $msg = 'OK';

            dibi::log(self::$sql
               . ";\r\n-- Result: $msg"
               . "\r\n-- Takes: " . sprintf('%0.3f', $timer * 1000) . ' ms'
               . "\r\n\r\n"
            );
        }

        if (dibi::$debug)
        {
            echo self::$error ? "\n[ERROR] " : "\n[OK] ";
            echo htmlSpecialChars(trim(strtr(self::$sql, "\r\n\t", '   ')));
            echo "\n<br />";
        }

        if (self::$error && dibi::$errorMode === self::ERR_EXCEPTION)
            throw self::$error;

        return $res;
    }





    /**
     * Generates and returns SQL query
     *
     * @param  array|mixed  one or more arguments
     * @return string
     */
    static public function test($args)
    {
        // receive arguments
        if (!is_array($args))
            $args = func_get_args();

        $dump = TRUE; // !!! todo

        // and generate SQL
        try {
            $trans = new DibiTranslator(self::getConnection(), self::$substs);
            $sql = $trans->translate($args);
            if ($dump) self::dump($sql);
            return $sql;

        } catch (DibiException $e) {
            if ($dump) self::dump($e->getSql());
            return FALSE;
        }
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
        // $sql = preg_replace('#  +#', ' ', $sql);

        $sql = wordwrap($sql, 100);
        $sql = htmlSpecialChars($sql);
        $sql = str_replace("\n", '<br />', $sql);

        // syntax highlight
        $sql = preg_replace_callback("#(/\*.+?\*/)|(\*\*.+?\*\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#", array('dibi', 'dumpHighlight'), $sql);
        $sql = '<pre class="dibi">' . $sql . '</pre>';

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
     * Error logging
     * EXPERIMENTAL
     */
    static public function log($message)
    {
        if (self::$logFile == NULL || self::$logMode == NULL) return;

        $f = fopen(self::$logFile, self::$logMode);
        fwrite($f, $message. "\r\n\r\n");
        fclose($f);
    }


} // class dibi
