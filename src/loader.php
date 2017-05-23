<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */


if (PHP_VERSION_ID < 70000) {
	throw new Exception('Dibi requires PHP 7.0 or newer.');
}


spl_autoload_register(function ($class) {
	$old2new = [
		'Dibi' => 'dibi.php',
		'DibiColumnInfo' => 'Dibi\Reflection\Column',
		'DibiConnection' => 'Dibi\Connection',
		'DibiDatabaseInfo' => 'Dibi\Reflection\Database',
		'DibiDataSource' => 'Dibi\DataSource',
		'DibiDateTime' => 'Dibi\DateTime',
		'DibiDriverException' => 'Dibi\DriverException',
		'DibiEvent' => 'Dibi\Event',
		'DibiException' => 'Dibi\Exception',
		'DibiFileLogger' => 'Dibi\Loggers\FileLogger',
		'DibiFirebirdDriver' => 'Dibi\Drivers\FirebirdDriver',
		'DibiFirePhpLogger' => 'Dibi\Loggers\FirePhpLogger',
		'DibiFluent' => 'Dibi\Fluent',
		'DibiForeignKeyInfo' => 'Dibi\Reflection\ForeignKey',
		'DibiHashMap' => 'Dibi\HashMap',
		'DibiHashMapBase' => 'Dibi\HashMapBase',
		'DibiIndexInfo' => 'Dibi\Reflection\Index',
		'DibiLiteral' => 'Dibi\Literal',
		'DibiMsSql2005Driver' => 'Dibi\Drivers\SqlsrvDriver',
		'DibiMsSql2005Reflector' => 'Dibi\Drivers\SqlsrvReflector',
		'DibiMsSqlDriver' => 'Dibi\Drivers\MsSqlDriver',
		'DibiMsSqlReflector' => 'Dibi\Drivers\MsSqlReflector',
		'DibiMySqliDriver' => 'Dibi\Drivers\MySqliDriver',
		'DibiMySqlReflector' => 'Dibi\Drivers\MySqlReflector',
		'DibiNotImplementedException' => 'Dibi\NotImplementedException',
		'DibiNotSupportedException' => 'Dibi\NotSupportedException',
		'DibiOdbcDriver' => 'Dibi\Drivers\OdbcDriver',
		'DibiOracleDriver' => 'Dibi\Drivers\OracleDriver',
		'DibiPcreException' => 'Dibi\PcreException',
		'DibiPdoDriver' => 'Dibi\Drivers\PdoDriver',
		'DibiPostgreDriver' => 'Dibi\Drivers\PostgreDriver',
		'DibiProcedureException' => 'Dibi\ProcedureException',
		'DibiResult' => 'Dibi\Result',
		'DibiResultInfo' => 'Dibi\Reflection\Result',
		'DibiResultIterator' => 'Dibi\ResultIterator',
		'DibiRow' => 'Dibi\Row',
		'DibiSqlite3Driver' => 'Dibi\Drivers\Sqlite3Driver',
		'DibiSqliteReflector' => 'Dibi\Drivers\SqliteReflector',
		'DibiTableInfo' => 'Dibi\Reflection\Table',
		'DibiTranslator' => 'Dibi\Translator',
		'IDataSource' => 'Dibi\IDataSource',
		'IDibiDriver' => 'Dibi\Driver',
		'IDibiReflector' => 'Dibi\Reflector',
		'IDibiResultDriver' => 'Dibi\ResultDriver',
		'Dibi\Drivers\MsSql2005Driver' => 'Dibi\Drivers\SqlsrvDriver',
		'Dibi\Drivers\MsSql2005Reflector' => 'Dibi\Drivers\SqlsrvReflector',
	];
	if (isset($old2new[$class])) {
		class_alias($old2new[$class], $class);
	}
});


// preload for compatiblity
class_alias('Dibi\DriverException', 'DibiDriverException');
class_alias('Dibi\Exception', 'DibiException');
class_alias('Dibi\ProcedureException', 'DibiProcedureException');
class_alias('Dibi\IDataSource', 'IDataSource');
