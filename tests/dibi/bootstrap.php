<?php

// The Nette Tester command-line runner can be
// invoked through the command: ../../vendor/bin/tester .

if (@!include __DIR__ . '/../../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}


// configure environment
Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');


// load connection
try {
	$config = Tester\Environment::loadData();
} catch (Exception $e) {
	$config = parse_ini_file(__DIR__ . '/../databases.ini', TRUE);
	$config = $config['sqlite3'];
}


// lock
define('TEMP_DIR', __DIR__ . '/../tmp');
@mkdir(TEMP_DIR); // @ - directory may already exist
Tester\Environment::lock($config['system'], TEMP_DIR);


// ODBC
if ($config['system'] === 'odbc') {
	copy(__DIR__ . '/data/odbc.mdb', TEMP_DIR . '/odbc.mdb');
	$config['dsn'] = str_replace('data/odbc.mdb', TEMP_DIR . '/odbc.mdb', $config['dsn']);
}


try {
	new DibiConnection($config);
} catch (DibiNotSupportedException $e) {
	Tester\Environment::skip($e->getMessage());
}


function test(\Closure $function)
{
	$function();
}


/** Replaces [] with driver-specific quotes */
function reformat($s)
{
	global $config;
	if (is_array($s)) {
		if (isset($s[$config['system']])) {
			return $s[$config['system']];
		}
		$s = $s[0];
	}
	if ($config['system'] === 'mysql') {
		return strtr($s, '[]', '``');
	} elseif ($config['system'] === 'pgsql') {
		return strtr($s, '[]', '""');
	} elseif ($config['system'] === 'odbc' || $config['system'] === 'sqlite') {
		return $s;
	} else {
		trigger_error("Unsupported driver $config[system]", E_USER_WARNING);
	}
}
