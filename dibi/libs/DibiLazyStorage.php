<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**#@+
 * Lazy cached storage.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
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

	public function __get($nm)
	{
		if (is_array($nm)) { // preg_replace_callback support
			$nm = $nm[1];
		}
		if ($nm == '') {
			throw new InvalidStateException('Missing identifier name.');
		}
		return $this->$nm = call_user_func($this->getCallback(), $nm);
	}

}
/**#@-*/
