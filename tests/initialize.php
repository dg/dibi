<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 * @package    Nette\Test
 */

require dirname(__FILE__) . '/NetteTest/TestHelpers.php';
require dirname(__FILE__) . '/NetteTest/Assert.php';
require dirname(__FILE__) . '/../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');

TestHelpers::startup();

if (function_exists('class_alias')) {
	class_alias('TestHelpers', 'T');
} else {
	class T extends TestHelpers {}
}

// load connections
define('DIR', dirname(__FILE__));
$config = parse_ini_file('config.ini', TRUE);
