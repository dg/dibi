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
 * @version    $Id$
 */



/**
 * Result-set single row.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiRow extends ArrayObject
{

	/**
	 * @param  array
	 */
	public function __construct($arr)
	{
		parent::__construct($arr, 2);
	}



	/**
	 * PHP < 5.3 workaround
	 * @return void
	 */
	public function __wakeup()
	{
		$this->setFlags(2);
	}

}
