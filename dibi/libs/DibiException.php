<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license", and/or
 * GPL license. For more information please see http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi common exception.
 *
 * @author     David Grudl
 */
class DibiException extends Exception implements IDebugPanel
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



	/********************* interface Nette\IDebugPanel ****************d*g**/



	/**
	 * Returns HTML code for custom tab.
	 * @return mixed
	 */
	public function getTab()
	{
		return 'SQL';
	}



	/**
	 * Returns HTML code for custom panel.
	 * @return mixed
	 */
	public function getPanel()
	{
		return $this->sql ? dibi::dump($this->sql, TRUE) : NULL;
	}



	/**
	 * Returns panel ID.
	 * @return string
	 */
	public function getId()
	{
		return __CLASS__;
	}

}




/**
 * database server exception.
 *
 * @author     David Grudl
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