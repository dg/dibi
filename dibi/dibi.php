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
 * @license    http://php7.org/dibi/license  dibi license
 * @version    0.9 (Revision: $WCREV$, Date: $WCDATE$)
 * @link       http://php7.org/dibi/
 * @package    dibi
 */


/**
 * Check PHP configuration
 */
if (version_compare(PHP_VERSION , '5.1.0', '<')) {
    throw new Exception('dibi needs PHP 5.1.0 or newer');
}




// libraries
require_once __FILE__ . '/../libs/NObject.php';
require_once __FILE__ . '/../libs/DibiException.php';
require_once __FILE__ . '/../libs/DibiDriver.php';
require_once __FILE__ . '/../libs/DibiResult.php';
require_once __FILE__ . '/../libs/DibiResultIterator.php';
require_once __FILE__ . '/../libs/DibiTranslator.php';
require_once __FILE__ . '/../libs/DibiLogger.php';





/**
 * Interface for user variable, used for generating SQL
 * @package dibi
 */
interface DibiVariableInterface
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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class dibi extends NClass
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
        FIELD_COUNTER =    'c', // counter or autoincrement, is integer

        // dibi version
        VERSION =          '0.9 (Revision: $WCREV$, Date: $WCDATE$)';


    /**
     * Connection registry storage for DibiDriver objects
     * @var DibiDriver[]
     */
    private static $registry = array();

    /**
     * Current connection
     * @var DibiDriver
     */
    private static $connection;

    /**
     * Substitutions for identifiers
     * @var array
     */
    private static $substs = array();

    /**
     * @see addHandler
     * @var array
     */
    private static $handlers = array();

    /**
     * Last SQL command @see dibi::query()
     * @var string
     */
    public static $sql;

    /**
     * Elapsed time for last query
     * @var int
     */
    public static $elapsedTime;

    /**
     * Elapsed time for all queries
     * @var int
     */
    public static $totalTime;

    /**
     * Number or queries
     * @var int
     */
    public static $numOfQueries = 0;

    /**
     * Default dibi driver
     * @var string
     */
    public static $defaultDriver = 'mysql';

    /**
     * Start time
     * @var int
     */
    private static $time;





    /**
     * Creates a new DibiDriver object and connects it to specified database
     *
     * @param  array|string connection parameters
     * @param  string       connection name
     * @return DibiDriver
     * @throws DibiException
     */
    public static function connect($config = array(), $name = 0)
    {
        // DSN string
        if (is_string($config)) {
            parse_str($config, $config);
        }

        if (!isset($config['driver'])) {
            $config['driver'] = self::$defaultDriver;
        }

        $class = "Dibi$config[driver]Driver";
        if (!class_exists($class)) {
            include_once __FILE__ . "/../drivers/$config[driver].php";

            if (!class_exists($class)) {
                throw new DibiException("Unable to create instance of dibi driver class '$class'.");
            }
        }

        // create connection object and store in list; like $connection = $class::connect($config);
        return self::$connection = self::$registry[$name] = new $class($config);
    }



    /**
     * Disconnects from database (doesn't destroy DibiDriver object)
     *
     * @return void
     */
    public static function disconnect()
    {
        self::getConnection()->disconnect();
    }



    /**
     * Returns TRUE when connection was established
     *
     * @return bool
     */
    public static function isConnected()
    {
        return (bool) self::$connection;
    }



    /**
     * Retrieve active connection
     *
     * @param  string   connection registy name
     * @return object   DibiDriver object.
     * @throws DibiException
     */
    public static function getConnection($name = NULL)
    {
        if ($name === NULL) {
            if (!self::$connection) {
                throw new DibiException('Dibi is not connected to database');
            }

            return self::$connection;
        }

        if (!isset(self::$registry[$name])) {
            throw new DibiException("There is no connection named '$name'.");
        }

        return self::$registry[$name];
    }



    /**
     * Change active connection
     *
     * @param  string   connection registy name
     * @return void
     * @throws DibiException
     */
    public static function activate($name)
    {
        self::$connection = self::getConnection($name);
    }



    /**
     * Generates and executes SQL query - Monostate for DibiDriver::query()
     *
     * @param  array|mixed    one or more arguments
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    public static function query($args)
    {
        if (!is_array($args)) $args = func_get_args();

        return self::getConnection()->query($args);
    }



    /**
     * Executes the SQL query - Monostate for DibiDriver::nativeQuery()
     *
     * @param string          SQL statement.
     * @return DibiResult     Result set object (if any)
     */
    public static function nativeQuery($sql)
    {
        return self::getConnection()->nativeQuery($sql);
    }



    /**
     * Generates and prints SQL query - Monostate for DibiDriver::test()
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    public static function test($args)
    {
        if (!is_array($args)) $args = func_get_args();

        return self::getConnection()->test($args);
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     * Monostate for DibiDriver::insertId()
     *
     * @param  string     optional sequence name for DibiPostgreDriver
     * @return int|FALSE  int on success or FALSE on failure
     */
    public static function insertId($sequence=NULL)
    {
        return self::getConnection()->insertId($sequence);
    }



    /**
     * Gets the number of affected rows
     * Monostate for DibiDriver::affectedRows()
     *
     * @return int  number of rows or FALSE on error
     */
    public static function affectedRows()
    {
        return self::getConnection()->affectedRows();
    }



    /**
     * Begins a transaction - Monostate for DibiDriver::begin()
     */
    public static function begin()
    {
        self::getConnection()->begin();
    }



    /**
     * Commits statements in a transaction - Monostate for DibiDriver::commit()
     */
    public static function commit()
    {
        self::getConnection()->commit();
    }



    /**
     * Rollback changes in a transaction - Monostate for DibiDriver::rollback()
     */
    public static function rollback()
    {
        self::getConnection()->rollback();
    }



    /**
     * Experimental; will be used in PHP 5.3
     */
    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(self::getConnection(), $name), $args);
    }



    /**
     * Create a new substitution pair for indentifiers
     *
     * @param string from
     * @param string to
     * @return void
     */
    public static function addSubst($expr, $subst)
    {
        self::$substs[':'.$expr.':'] = $subst;
    }



    /**
     * Remove substitution pair
     *
     * @param mixed from or TRUE
     * @return void
     */
    public static function removeSubst($expr)
    {
        if ($expr === TRUE) {
            self::$substs = array();
        } else {
            unset(self::$substs[':'.$expr.':']);
        }
    }



    /**
     * Returns substitution pairs
     *
     * @return array
     */
    public static function getSubst()
    {
        return self::$substs;
    }



    /**
     * Add new event handler
     *
     * @param callback
     * @return void
     * @throws InvalidArgumentException
     */
    public static function addHandler($callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException("Invalid callback");
        }

        self::$handlers[] = $callback;
    }



    /**
     * Event notification (events: exception, connected, beforeQuery, afterQuery, begin, commit, rollback)
     *
     * @param string event name
     * @param DibiDriver
     * @param mixed
     * @return void
     */
    public static function notify($event, DibiDriver $driver = NULL, $arg = NULL)
    {
        if ($event === 'beforeQuery') {
            self::$numOfQueries++;
            self::$elapsedTime = FALSE;
            self::$time = -microtime(TRUE);
            self::$sql = $arg;

        } elseif ($event === 'afterQuery') {
            self::$elapsedTime = self::$time + microtime(TRUE);
            self::$totalTime += self::$elapsedTime;
        }

        foreach (self::$handlers as $handler) {
            call_user_func($handler, $event, $driver, $arg);
        }
    }



    /**
     * Enable profiler & logger
     *
     * @param string  filename
     * @param bool    log all queries?
     * @return DibiProfiler
     */
    public static function startLogger($file, $logQueries = FALSE)
    {
        $logger = new DibiLogger($file);
        $logger->logQueries = $logQueries;
        self::addHandler(array($logger, 'handler'));
        return $logger;
    }



    /**
     * Prints out a syntax highlighted version of the SQL command or DibiResult
     *
     * @param string|DibiResult
     * @param bool  return or print?
     * @return string
     */
    public static function dump($sql = NULL, $return = FALSE)
    {
        ob_start();
        if ($sql instanceof DibiResult) {
            $sql->dump();

        } else {
            if ($sql === NULL) $sql = self::$sql;

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
            $sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|\\b($keywords1)\\b|\\b($keywords2)\\b#", array('dibi', 'highlightCallback'), $sql);
            echo '<pre class="dump">', $sql, "</pre>\n";
        }

        if ($return) {
            return ob_get_clean();
        } else {
            ob_end_flush();
        }
    }



    private static function highlightCallback($matches)
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


}
