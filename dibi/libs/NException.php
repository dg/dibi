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
 * @copyright  Copyright (c) 2004, 2007 David Grudl
 * @license    http://php7.org/nette/license  Nette license
 * @link       http://php7.org/nette/
 * @package    Nette
 */



/**
 * Nette Exception base class
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2007 David Grudl
 * @license    http://php7.org/nette/license  Nette license
 * @link       http://php7.org/nette/
 * @package    Nette
 */
class NException extends Exception
{
    /** @var Exception */
    private $cause;

    /** @var callback */
    private static $oldHandler;

    /** @var string */
    private static $handlerClass;




    /**
     * Initializes the cause of this throwable to the specified value
     *
     * @param  Exception
     * @return void
     */
    public function initCause(Exception $cause)
    {
        if ($this->cause === NULL) {
            $this->cause = $cause;
        } else {
            throw new BadMethodCallException('Cause was already assigned');
        }
    }



    /**
     * Gets the Exception instance that caused the current exception
     *
     * @return Exception
     */
    public function getCause()
    {
        return $this->cause;
    }



    /**
     * Returns string represenation of exception
     *
     * @return void
     */
    public function __toString()
    {
        return parent::__toString() . ($this->cause === NULL ? '' : "\nCaused by " . $this->cause->__toString());
    }



    /**
     * Enables converting all PHP errors to exceptions
     *
     * @param  Exception class to be thrown
     * @return void
     */
    public static function catchError($class = __CLASS__)
    {
        self::$oldHandler = set_error_handler(array(__CLASS__, '_errorHandler'), E_ALL);
        self::$handlerClass = $class;
    }



    /**
     * Disables converting errors to exceptions
     *
     * @return void
     */
    public static function restore()
    {
        if (self::$oldHandler !== NULL) {
            set_error_handler(self::$oldHandler);
            self::$oldHandler = NULL;
        } else {
            restore_error_handler();
        }
    }


    /**
     * Internal error handler
     */
    public static function _errorHandler($code, $message, $file, $line, $context)
    {
        self::restore();

        if (ini_get('html_errors')) {
            $message = strip_tags($message);
        }

        throw new self::$handlerClass($message, $code);
    }

}
