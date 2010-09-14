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



/**#@+
 * Lazy cached storage.
 *
 * @author     David Grudl
 * @internal
 */
abstract class DibiLazyStorageBase
{
	private $callback;


	public function __construct($callback)
	{
		$this->setCallback($callback);
	}



	public function setCallback($callback)
	{
		if (!is_callable($callback)) {
			$able = is_callable($callback, TRUE, $textual);
			throw new InvalidArgumentException("Handler '$textual' is not " . ($able ? 'callable.' : 'valid PHP callback.'));
		}
		$this->callback = $callback;
	}



	public function getCallback()
	{
		return $this->callback;
	}

}



final class DibiLazyStorage extends DibiLazyStorageBase
{

	public function __set($nm, $val)
	{
		if ($nm == '') {
			$nm = "\xFF";
		}
		$this->$nm = $val;
	}



	public function __get($nm)
	{
		if ($nm == '') {
			$nm = "\xFF";
			return isset($this->$nm) ? $this->$nm : $this->$nm = call_user_func($this->getCallback(), '');
		} else {
			return $this->$nm = call_user_func($this->getCallback(), $nm);
		}
	}

}
/**#@-*/
