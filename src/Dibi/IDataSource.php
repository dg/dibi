<?php

/**
 * This file is part of the Dibi, smart database abstraction layer (https://dibi.nette.org)
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Dibi;


/**
 * Provides an interface between a dataset and data-aware components.
 */
interface IDataSource extends \Countable, \IteratorAggregate
{
	//function \IteratorAggregate::getIterator();
	//function \Countable::count();
}
