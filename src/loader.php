<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


/**
 * Check PHP configuration.
 */
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	throw new Exception('dibi needs PHP 5.2.0 or newer.');
}


require_once dirname(__FILE__) . '/Dibi/interfaces.php';
require_once dirname(__FILE__) . '/Dibi/Dibi.php';
require_once dirname(__FILE__) . '/Dibi/DateTime.php';
require_once dirname(__FILE__) . '/Dibi/Object.php';
require_once dirname(__FILE__) . '/Dibi/Literal.php';
require_once dirname(__FILE__) . '/Dibi/HashMap.php';
require_once dirname(__FILE__) . '/Dibi/Exception.php';
require_once dirname(__FILE__) . '/Dibi/Connection.php';
require_once dirname(__FILE__) . '/Dibi/Result.php';
require_once dirname(__FILE__) . '/Dibi/ResultIterator.php';
require_once dirname(__FILE__) . '/Dibi/Row.php';
require_once dirname(__FILE__) . '/Dibi/Translator.php';
require_once dirname(__FILE__) . '/Dibi/DataSource.php';
require_once dirname(__FILE__) . '/Dibi/Fluent.php';
require_once dirname(__FILE__) . '/Dibi/Reflection/DatabaseInfo.php';
require_once dirname(__FILE__) . '/Dibi/Event.php';
require_once dirname(__FILE__) . '/Dibi/Loggers/FileLogger.php';
require_once dirname(__FILE__) . '/Dibi/Loggers/FirePhpLogger.php';
