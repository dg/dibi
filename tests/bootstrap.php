<?php

// The Nette Tester command-line runner can be
// invoked through the command: ../../vendor/bin/tester .

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}


// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');
class_alias('Tester\Assert', 'Assert');


// load connections
define('DIR', dirname(__FILE__));
$config = parse_ini_file(dirname(__FILE__) . '/config.ini', TRUE);
