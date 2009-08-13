<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi common exception.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiException extends Exception implements /*Nette\*/IDebuggable
{
	/** @var string */
	private $sql;


	/**
	 * Construct a dibi exception.
	 * @param  string  Message describing the exception
	 * @param  int     Some code
	 * @param  string SQL command
	 */
	public function __construct($message = NULL, $code = 0, $sql = NULL)
	{
		parent::__construct($message, (int) $code);
		$this->sql = $sql;
		// TODO: add $profiler->exception($this);
	}



	/**
	 * @return string  The SQL passed to the constructor
	 */
	final public function getSql()
	{
		return $this->sql;
	}



	/**
	 * @return string  string represenation of exception with SQL command
	 */
	public function __toString()
	{
		return parent::__toString() . ($this->sql ? "\nSQL: " . $this->sql : '');
	}



	/********************* interface Nette\IDebuggable ****************d*g**/


	/**
	 * Returns custom panels.
	 * @return array
	 */
	public function getPanels()
	{
		$panels = array();
		if ($this->sql !== NULL) {
			$panels['SQL'] = array(
				'expanded' => TRUE,
				'content' => dibi::dump($this->sql, TRUE),
			);
		}
		return $panels;
	}

}




/**
 * database server exception.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiDriverException extends DibiException
{

	/********************* error catching ****************d*g**/



	/** @var string */
	private static $errorMsg;



	/**
	 * Starts catching potential errors/warnings.
	 * @return void
	 */
	public static function tryError()
	{
		set_error_handler(array(__CLASS__, '_errorHandler'), E_ALL);
		self::$errorMsg = NULL;
	}



	/**
	 * Returns catched error/warning message.
	 * @param  string  catched message
	 * @return bool
	 */
	public static function catchError(& $message)
	{
		restore_error_handler();
		$message = self::$errorMsg;
		self::$errorMsg = NULL;
		return $message !== NULL;
	}



	/**
	 * Internal error handler. Do not call directly.
	 * @internal
	 */
	public static function _errorHandler($code, $message)
	{
		restore_error_handler();

		if (ini_get('html_errors')) {
			$message = strip_tags($message);
			$message = html_entity_decode($message);
		}

		self::$errorMsg = $message;
	}

}