<?php

/**
 * Nette Framework
 *
 * Copyright (c) 2004, 2008 David Grudl (http://www.davidgrudl.com)
 *
 * This source file is subject to the "Nette license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://nettephp.com/
 *
 * @copyright  Copyright (c) 2004, 2008 David Grudl
 * @license    http://nettephp.com/license  Nette license
 * @link       http://nettephp.com/
 * @category   Nette
 * @package    Nette
 */

// no namespace;



/*
some useful SPL exception:

- LogicException
   - InvalidArgumentException
   - LengthException
- RuntimeException
   - OutOfBoundsException
   - UnexpectedValueException

other SPL exceptions are ambiguous; do not use them
*/



/**
 * The exception that is thrown when the value of an argument is
 * outside the allowable range of values as defined by the invoked method.
 */
class ArgumentOutOfRangeException extends InvalidArgumentException
{
}



/**
 * The exception that is thrown when a method call is invalid for the object's
 * current state, method has been invoked at an illegal or inappropriate time.
 */
class InvalidStateException extends RuntimeException // or InvalidOperationException?
{
}



/**
 * The exception that is thrown when a requested method or operation is not implemented.
 */
class NotImplementedException extends LogicException
{
}



/**
 * The exception that is thrown when an invoked method is not supported. For scenarios where
 * it is sometimes possible to perform the requested operation, see InvalidStateException.
 */
class NotSupportedException extends LogicException
{
}



/**
 * The exception that is thrown when accessing a class member (property or method) fails.
 */
class MemberAccessException extends LogicException
{
}



/**
 * The exception that is thrown when an I/O error occurs.
 */
class IOException extends RuntimeException
{
}



/**
 * The exception that is thrown when accessing a file that does not exist on disk.
 */
class FileNotFoundException extends IOException
{
}



/**
 * The exception that is thrown when part of a file or directory cannot be found.
 */
class DirectoryNotFoundException extends IOException
{
}



/**
 * User attempt to terminate the current script.
 */
class AbortException extends RuntimeException
{
}
