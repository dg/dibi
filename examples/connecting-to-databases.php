<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Connecting to Databases | dibi</h1>

<?php

require __DIR__ . '/../src/dibi.php';


// connects to SQlite using dibi class
echo '<p>Connecting to Sqlite: ';
try {
	dibi::connect([
		'driver' => 'sqlite3',
		'database' => 'data/sample.s3db',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to SQlite using DibiConnection object
echo '<p>Connecting to Sqlite: ';
try {
	$connection = new DibiConnection([
		'driver' => 'sqlite3',
		'database' => 'data/sample.s3db',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to MySQL using DSN
echo '<p>Connecting to MySQL: ';
try {
	dibi::connect('driver=mysql&host=localhost&username=root&password=xxx&database=test&charset=cp1250');
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to MySQLi using array
echo '<p>Connecting to MySQLi: ';
try {
	dibi::connect([
		'driver' => 'mysqli',
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'xxx',
		'database' => 'dibi',
		'options' => [
			MYSQLI_OPT_CONNECT_TIMEOUT => 30,
		],
		'flags' => MYSQLI_CLIENT_COMPRESS,
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to ODBC
echo '<p>Connecting to ODBC: ';
try {
	dibi::connect([
		'driver' => 'odbc',
		'username' => 'root',
		'password' => '***',
		'dsn' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq='.__DIR__.'/data/sample.mdb',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to PostgreSql
echo '<p>Connecting to PostgreSql: ';
try {
	dibi::connect([
		'driver' => 'postgre',
		'string' => 'host=localhost port=5432 dbname=mary',
		'persistent' => TRUE,
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to PDO
echo '<p>Connecting to Sqlite via PDO: ';
try {
	dibi::connect([
		'driver' => 'pdo',
		'dsn' => 'sqlite::memory:',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to MS SQL
echo '<p>Connecting to MS SQL: ';
try {
	dibi::connect([
		'driver' => 'mssql',
		'host' => 'localhost',
		'username' => 'root',
		'password' => 'xxx',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to MS SQL 2005
echo '<p>Connecting to MS SQL 2005: ';
try {
	dibi::connect([
		'driver' => 'mssql2005',
		'host' => '(local)',
		'username' => 'Administrator',
		'password' => 'xxx',
		'database' => 'main',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";


// connects to Oracle
echo '<p>Connecting to Oracle: ';
try {
	dibi::connect([
		'driver' => 'oracle',
		'username' => 'root',
		'password' => 'xxx',
		'database' => 'db',
	]);
	echo 'OK';
} catch (DibiException $e) {
	echo get_class($e), ': ', $e->getMessage(), "\n";
}
echo "</p>\n";
