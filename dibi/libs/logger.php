<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    New BSD License
 * @version    $Revision$ $Date$
 * @category   Database
 * @package    Dibi
 */



/**
 * dibi basic logger & profiler
 */
final class DibiLogger
{
    /** @var string  Name of the file where SQL errors should be logged */
    private $file;

    /** @var bool */
    public $logErrors = TRUE;

    /** @var bool */
    public $logQueries = TRUE;



    /**
     * @param string  filename
     */
    public function __construct($file)
    {
        $this->file = $file;
    }



    /**
     * Event handler (events: exception, connected, beforeQuery, afterQuery, begin, commit, rollback)
     *
     * @param string event name
     * @param mixed
     * @param mixed
     * @return void
     */
    public function handler($event, $driver, $arg)
    {
        if ($event === 'afterQuery' && $this->logQueries) {
            $this->write(
                "OK: " . dibi::$sql
                . ($arg instanceof DibiResult ? ";\n-- rows: " . $arg->rowCount() : '')
                . "\n-- takes: " . sprintf('%0.3f', dibi::$elapsedTime * 1000) . ' ms'
                . "\n-- driver: " . $driver->getConfig('driver')
                . "\n-- " . date('Y-m-d H:i:s')
                . "\n\n"
            );
            return;
        }

        if ($event === 'exception' && $this->logErrors) {
            // $arg is DibiDatabaseException
            $message = $arg->getMessage();
            $code = $arg->getCode();
            if ($code) {
                $message = "[$code] $message";
            }

            $this->write(
                "ERROR: $message"
                . "\n-- SQL: " . dibi::$sql
                . "\n-- driver: " //. $driver->getConfig('driver')
                . ";\n-- " . date('Y-m-d H:i:s')
                . "\n\n"
            );
            return;
        }
    }



    private function write($message)
    {
        $handle = fopen($this->file, 'a');
        if (!$handle) return; // or throw exception?

        flock($handle, LOCK_EX);
        fwrite($handle, $message);
        fclose($handle);
    }



    /**#@+
     * Access to undeclared property
     * @throws Exception
     */
    private function &__get($name) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    private function __set($name, $value) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    private function __unset($name) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$name"); }
    /**#@-*/

}
