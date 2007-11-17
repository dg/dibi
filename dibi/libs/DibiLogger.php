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
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @link       http://php7.org/dibi/
 * @package    dibi
 */



/**
 * dibi basic logger & profiler (experimental)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
final class DibiLogger extends NObject
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
     * @param DibiConnection
     * @param string event name
     * @param mixed
     * @return void
     */
    public function handler($connection, $event, $arg)
    {
        if ($event === 'afterQuery' && $this->logQueries) {
            $this->write(
                "OK: " . dibi::$sql
                . ($arg instanceof DibiResult ? ";\n-- rows: " . count($arg) : '')
                . "\n-- takes: " . sprintf('%0.3f', dibi::$elapsedTime * 1000) . ' ms'
                . "\n-- driver: " . $connection->getConfig('driver')
                . "\n-- " . date('Y-m-d H:i:s')
                . "\n\n"
            );
            return;
        }

        if ($event === 'exception' && $this->logErrors) {
            // $arg is DibiDriverException
            $message = $arg->getMessage();
            $code = $arg->getCode();
            if ($code) {
                $message = "[$code] $message";
            }

            $this->write(
                "ERROR: $message"
                . "\n-- SQL: " . dibi::$sql
                . "\n-- driver: " //. $connection->getConfig('driver')
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

}
