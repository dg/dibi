<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 * @package    Nette\Test
 */

require dirname(__FILE__) . '/../vendor/nette/tester/Tester/bootstrap.php';
require dirname(__FILE__) . '/../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');
class_alias('Tester\Assert', 'Assert');

// load connections
define('DIR', dirname(__FILE__));
$config = parse_ini_file(dirname(__FILE__) . '/config.ini', TRUE);
