<?php

return array(
	'mysql' => array(
		'driver' => 'mysql',
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'xxx',
		'charset' => 'utf8',
	),

	'mysqli' => array(
		'driver' => 'mysqli',
		'host' => 'localhost',
		'username' => 'dibi',
		'password' => 'dibi',
		'charset' => 'utf8',
	),

	'sqlite' => array(
		'driver' => 'sqlite',
		'database' => dirname(__FILE__) . '/data/sample.sdb',
	),

	'sqlite3' => array(
		'driver' => 'sqlite3',
		'database' => dirname(__FILE__) . '/data/sample.sdb3',
	),

	'odbc' => array(
		'driver' => 'odbc',
		'username' => 'dibi',
		'password' => 'dibi',
		'dsn' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq=' . dirname(__FILE__) . '/data/sample.mdb',
	),

	'postgresql' => array(
		'driver' => 'postgre',
		'host' => 'localhost',
		'port' => '5432',
		'username' => 'dibi',
		'password' => 'dibi',
		'persistent' => '1',
	),

	'sqlite-pdo' => array(
		'driver' => 'pdo',
		'dsn' => 'sqlite2::' . dirname(__FILE__) . '/data/sample.sdb',
	),

	'mysql-pdo' => array(
		'driver' => 'pdo',
		'dsn' => 'mysql:host=localhost',
		'username' => 'dibi',
		'password' => 'dibi',
	),

	'mssql' => array(
		'driver' => 'mssql',
		'host' => 'localhost',
		'username' => 'dibi',
		'password' => 'dibi',
	),

	'mssql2005' => array(
		'driver' => 'mssql2005',
		'host' => '(local)',
		'username' => 'dibi',
		'password' => 'dibi',
	),

	'oracle' => array(
		'driver' => 'oracle',
		'username' => 'dibi',
		'password' => 'dibi',
	),
);
