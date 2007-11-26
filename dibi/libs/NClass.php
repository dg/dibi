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
 * NClass is the ultimate ancestor of all uninstantiable classes.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2004, 2007 David Grudl
 * @license    http://php7.org/nette/license  Nette license
 * @link       http://php7.org/nette/
 * @package    Nette
 */
abstract class NClass
{

    final public function __construct()
    {
        throw new LogicException("Cannot instantiate static class " . get_class($this));
    }

}
