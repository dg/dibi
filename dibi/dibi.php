<?php

/**
 * dibi - smart database abstraction layer (http://dibiphp.com)
 *
 * Copyright (c) 2005, 2012 David Grudl (http://davidgrudl.com)
 */


/**
 * Check PHP configuration.
 */
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	throw new Exception('dibi needs PHP 5.2.0 or newer.');
}


require_once dirname(__FILE__) . '/libs/interfaces.php';
require_once dirname(__FILE__) . '/libs/Dibi.php';
require_once dirname(__FILE__) . '/libs/DibiDateTime.php';
require_once dirname(__FILE__) . '/libs/DibiObject.php';
require_once dirname(__FILE__) . '/libs/DibiLiteral.php';
require_once dirname(__FILE__) . '/libs/DibiHashMap.php';
require_once dirname(__FILE__) . '/libs/DibiException.php';
require_once dirname(__FILE__) . '/libs/DibiConnection.php';
require_once dirname(__FILE__) . '/libs/DibiResult.php';
require_once dirname(__FILE__) . '/libs/DibiResultIterator.php';
require_once dirname(__FILE__) . '/libs/DibiRow.php';
require_once dirname(__FILE__) . '/libs/DibiTranslator.php';
require_once dirname(__FILE__) . '/libs/DibiDataSource.php';
require_once dirname(__FILE__) . '/libs/DibiFluent.php';
require_once dirname(__FILE__) . '/libs/DibiDatabaseInfo.php';
require_once dirname(__FILE__) . '/libs/DibiEvent.php';
require_once dirname(__FILE__) . '/libs/DibiFileLogger.php';
require_once dirname(__FILE__) . '/libs/DibiFirePhpLogger.php';

if (interface_exists('Nette\Diagnostics\IBarPanel') || interface_exists('IBarPanel')) {
	require_once dirname(__FILE__) . '/bridges/Nette-2.1/DibiNettePanel.php';
}
