<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @version    0.9 (Revision: $WCREV$, Date: $WCDATE$)
 * @link       http://dibiphp.com/
 * @package    dibi
 */


/**
 * Check PHP configuration
 */
if (version_compare(PHP_VERSION , '5.1.0', '<')) {
    throw new Exception('dibi needs PHP 5.1.0 or newer');
}




// nette libraries
if (!class_exists('NObject', FALSE)) { require_once dirname(__FILE__) . '/libs/NObject.php'; }
if (!class_exists('NException', FALSE)) { require_once dirname(__FILE__) . '/libs/NException.php'; }

// dibi libraries
require_once dirname(__FILE__) . '/libs/interfaces.php';
require_once dirname(__FILE__) . '/libs/DibiException.php';
require_once dirname(__FILE__) . '/libs/DibiConnection.php';
require_once dirname(__FILE__) . '/libs/DibiResult.php';
require_once dirname(__FILE__) . '/libs/DibiResultIterator.php';
require_once dirname(__FILE__) . '/libs/DibiTranslator.php';
require_once dirname(__FILE__) . '/libs/DibiLogger.php';
require_once dirname(__FILE__) . '/libs/DibiVariable.php';
require_once dirname(__FILE__) . '/libs/DibiTable.php';
require_once dirname(__FILE__) . '/libs/DibiDataSource.php';





/**
 * Interface for database drivers
 *
 * This class is static container class for creating DB objects and
 * store connections info.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
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
        FIELD_COUNTER =    'C', // counter or autoincrement, is integer
        IDENTIFIER =       'n',

        // dibi version
        VERSION =          '0.9 (Revision: $WCREV$, Date: $WCDATE$)';


    /**
     * Connection registry storage for DibiConnection objects
     * @var DibiConnection[]
     */
    private static $registry = array();

    /**
     * Current connection
     * @var DibiConnection
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
     * Static class - cannot be instantiated
     */
    final public function __construct()
    {
        throw new LogicException("Cannot instantiate static class " . get_class($this));
    }



    /**
     * Creates a new DibiConnection object and connects it to specified database
     *
     * @param  array|string connection parameters
     * @param  string       connection name
     * @return DibiConnection
     * @throws DibiException
     */
    public static function connect($config = array(), $name = 0)
    {
        if (is_array($config)) {
            $config['name'] = $name;
        } else {
            $config .= '&name=' . urlencode($name);
        }
        return self::$connection = self::$registry[$name] = new DibiConnection($config);
    }



    /**
     * Disconnects from database (doesn't destroy DibiConnection object)
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
     * @return object   DibiConnection object.
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
     * Generates and executes SQL query - Monostate for DibiConnection::query()
     *
     * @param  array|mixed    one or more arguments
     * @return DibiResult     Result set object (if any)
     * @throws DibiException
     */
    public static function query($args)
    {
        $args = func_get_args();
        return self::getConnection()->query($args);
    }



    /**
     * Executes the SQL query - Monostate for DibiConnection::nativeQuery()
     *
     * @param  string         SQL statement.
     * @return DibiResult     Result set object (if any)
     */
    public static function nativeQuery($sql)
    {
        return self::getConnection()->nativeQuery($sql);
    }



    /**
     * Generates and prints SQL query - Monostate for DibiConnection::test()
     *
     * @param  array|mixed  one or more arguments
     * @return bool
     */
    public static function test($args)
    {
        $args = func_get_args();
        return self::getConnection()->test($args);
    }



    /**
     * Executes SQL query and fetch result - Monostate for DibiConnection::query() & fetch()
     *
     * @param  array|mixed    one or more arguments
     * @return array
     * @throws DibiException
     */
    public static function fetch($args)
    {
        $args = func_get_args();
        return self::getConnection()->query($args)->fetch();
    }



    /**
     * Executes SQL query and fetch results - Monostate for DibiConnection::query() & fetchAll()
     *
     * @param  array|mixed    one or more arguments
     * @return array
     * @throws DibiException
     */
    public static function fetchAll($args)
    {
        $args = func_get_args();
        return self::getConnection()->query($args)->fetchAll();
    }



    /**
     * Executes SQL query and fetch first column - Monostate for DibiConnection::query() & fetchSingle()
     *
     * @param  array|mixed    one or more arguments
     * @return string
     * @throws DibiException
     */
    public static function fetchSingle($args)
    {
        $args = func_get_args();
        return self::getConnection()->query($args)->fetchSingle();
    }



    /**
     * Gets the number of affected rows
     * Monostate for DibiConnection::affectedRows()
     *
     * @return int  number of rows
     * @throws DibiException
     */
    public static function affectedRows()
    {
        return self::getConnection()->affectedRows();
    }



    /**
     * Retrieves the ID generated for an AUTO_INCREMENT column by the previous INSERT query
     * Monostate for DibiConnection::insertId()
     *
     * @param  string     optional sequence name
     * @return int
     * @throws DibiException
     */
    public static function insertId($sequence=NULL)
    {
        return self::getConnection()->insertId($sequence);
    }



    /**
     * Begins a transaction - Monostate for DibiConnection::begin()
     * @return void
     * @throws DibiException
     */
    public static function begin()
    {
        self::getConnection()->begin();
    }



    /**
     * Commits statements in a transaction - Monostate for DibiConnection::commit()
     * @return void
     * @throws DibiException
     */
    public static function commit()
    {
        self::getConnection()->commit();
    }



    /**
     * Rollback changes in a transaction - Monostate for DibiConnection::rollback()
     * @return void
     * @throws DibiException
     */
    public static function rollback()
    {
        self::getConnection()->rollback();
    }



    /**
     * Import SQL dump from file - extreme fast!
     *
     * @param  string  filename
     * @return int  count of sql commands
     */
    public static function loadFile($file)
    {
        return self::getConnection()->loadFile($file);
    }



    /**
     * Experimental; will be used in PHP 5.3
     */
    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(self::getConnection(), $name), $args);
    }



    /**
     * Pseudotype for timestamp representation
     *
     * @param  mixed  datetime
     * @return DibiVariable
     */
    public static function datetime($time = NULL)
    {
        if ($time === NULL) {
            $time = time(); // current time
        } elseif (is_string($time)) {
            $time = strtotime($time); // try convert to timestamp
        } else {
            $time = (int) $time;
        }
        return new DibiVariable($time, dibi::FIELD_DATETIME);
    }



    /**
     * Pseudotype for date representation
     *
     * @param  mixed  date
     * @return DibiVariable
     */
    public static function date($date = NULL)
    {
        $var = self::datetime($date);
        $var->modifier = dibi::FIELD_DATE;
        return $var;
    }



    /**
     * Create a new substitution pair for indentifiers
     *
     * @param  string from
     * @param  string to
     * @return void
     */
    public static function addSubst($expr, $subst)
    {
        self::$substs[':'.$expr.':'] = $subst;
    }



    /**
     * Remove substitution pair
     *
     * @param  mixed from or TRUE
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
     * @param  callback
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
     * @param  DibiConnection
     * @param  string event name
     * @param  mixed
     * @return void
     */
    public static function notify(DibiConnection $connection = NULL, $event, $arg = NULL)
    {
        foreach (self::$handlers as $handler) {
            call_user_func($handler, $connection, $event, $arg);
        }
    }



    /**
     * Enable profiler & logger
     *
     * @param  string  filename
     * @param  bool    log all queries?
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
     * @param  string|DibiResult
     * @param  bool  return output instead of printing it?
     * @return string
     */
    public static function dump($sql = NULL, $return = FALSE)
    {
        ob_start();
        if ($sql instanceof DibiResult) {
            $sql->dump();

        } else {
            if ($sql === NULL) $sql = self::$sql;

            static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|TRUE|FALSE';
            static $keywords1 = 'SELECT|UPDATE|INSERT(?:\s+INTO)|REPLACE(?:\s+INTO)|DELETE|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN';

            // insert new lines
            $sql = ' ' . $sql;
            $sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

            // reduce spaces
            $sql = preg_replace('# {2,}#', ' ', $sql);

            $sql = wordwrap($sql, 100);
            $sql = htmlSpecialChars($sql);
            $sql = preg_replace("#\n{2,}#", "\n", $sql);

            // syntax highlight
            $sql = preg_replace_callback("#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is", array('dibi', 'highlightCallback'), $sql);
            $sql = trim($sql);
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
            return '<em style="color:gray">' . $matches[1] . '</em>';

        if (!empty($matches[2])) // error
            return '<strong style="color:red">' . $matches[2] . '</strong>';

        if (!empty($matches[3])) // most important keywords
            return '<strong style="color:blue">' . $matches[3] . '</strong>';

        if (!empty($matches[4])) // other keywords
            return '<strong style="color:green">' . $matches[4] . '</strong>';
    }


}
