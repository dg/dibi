<?php

declare(strict_types=1);

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
} catch (Throwable $e) {
	$config = parse_ini_file(__DIR__ . '/../databases.ini', process_sections: true);
	$config = reset($config);
}

if (isset($config['port'])) {
	$config['port'] = (int) $config['port'];
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


function test(string $title, Closure $function): void
{
	$function();
}


/** Replaces [] with driver-specific quotes */
function reformat($s)
{
	$config = $GLOBALS['config'];
	if (is_array($s)) {
		if (isset($s[$config['system']])) {
			return $s[$config['system']];
		}
		$s = $s[0];
	}
	if ($config['system'] === 'mysql') {
		return strtr($s, '[]', '``');
	} elseif ($config['system'] === 'postgre') {
		return strtr($s, '[]', '""');
	} elseif (in_array($config['system'], ['odbc', 'sqlite', 'sqlsrv'], true)) {
		return $s;
	} else {
		trigger_error("Unsupported driver $config[system]", E_USER_WARNING);
	}
}


function num($n)
{
	$config = $GLOBALS['config'];
	if (substr($config['dsn'] ?? '', 0, 5) === 'odbc:') {
		$n = is_float($n) ? "$n.0" : (string) $n;
	}
	return $n;
}
