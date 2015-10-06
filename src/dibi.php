<?php

/**
 * dibi - smart database abstraction layer (http://dibiphp.com)
 *
 * Copyright (c) 2005, 2012 David Grudl (https://davidgrudl.com)
 */


if (PHP_VERSION_ID < 50404) {
	throw new Exception('Dibi requires PHP 5.4.4 or newer.');
}


spl_autoload_register(function ($class) {
	static $map = [
		'dibi' => 'dibi.php',
		'Dibi' => 'dibi.php',
		'Dibi\Bridges\Nette\DibiExtension22' => 'Bridges/Nette/DibiExtension22.php',
		'Dibi\Bridges\Tracy\Panel' => 'Bridges/Tracy/Panel.php',
		'DibiColumnInfo' => 'Reflection/Column.php',
		'DibiConnection' => 'Connection.php',
		'DibiDatabaseInfo' => 'Reflection/Database.php',
		'DibiDataSource' => 'DataSource.php',
		'DibiDateTime' => 'DateTime.php',
		'DibiDriverException' => 'exceptions.php',
		'DibiEvent' => 'Event.php',
		'DibiException' => 'exceptions.php',
		'DibiFileLogger' => 'Loggers/FileLogger.php',
		'DibiFirebirdDriver' => 'Drivers/FirebirdDriver.php',
		'DibiFirePhpLogger' => 'Loggers/FirePhpLogger.php',
		'DibiFluent' => 'Fluent.php',
		'DibiForeignKeyInfo' => 'Reflection/ForeignKey.php',
		'DibiHashMap' => 'HashMap.php',
		'DibiHashMapBase' => 'HashMap.php',
		'DibiHelpers' => 'Helpers.php',
		'DibiIndexInfo' => 'Reflection/Index.php',
		'DibiLiteral' => 'Literal.php',
		'DibiMsSql2005Driver' => 'Drivers/MsSql2005Driver.php',
		'DibiMsSql2005Reflector' => 'Drivers/MsSql2005Reflector.php',
		'DibiMsSqlDriver' => 'Drivers/MsSqlDriver.php',
		'DibiMsSqlReflector' => 'Drivers/MsSqlReflector.php',
		'DibiMySqlDriver' => 'Drivers/MySqlDriver.php',
		'DibiMySqliDriver' => 'Drivers/MySqliDriver.php',
		'DibiMySqlReflector' => 'Drivers/MySqlReflector.php',
		'DibiNette21Extension' => 'Bridges/Nette/DibiExtension21.php',
		'DibiNettePanel' => 'Bridges/Nette/Panel.php',
		'DibiNotImplementedException' => 'exceptions.php',
		'DibiNotSupportedException' => 'exceptions.php',
		'DibiStrict' => 'Strict.php',
		'DibiOdbcDriver' => 'Drivers/OdbcDriver.php',
		'DibiOracleDriver' => 'Drivers/OracleDriver.php',
		'DibiPcreException' => 'exceptions.php',
		'DibiPdoDriver' => 'Drivers/PdoDriver.php',
		'DibiPostgreDriver' => 'Drivers/PostgreDriver.php',
		'DibiProcedureException' => 'exceptions.php',
		'DibiResult' => 'Result.php',
		'DibiResultInfo' => 'Reflection/Result.php',
		'DibiResultIterator' => 'ResultIterator.php',
		'DibiRow' => 'Row.php',
		'DibiSqlite3Driver' => 'Drivers/Sqlite3Driver.php',
		'DibiSqliteReflector' => 'Drivers/SqliteReflector.php',
		'DibiTableInfo' => 'Reflection/Table.php',
		'DibiTranslator' => 'Translator.php',
		'DibiType' => 'Type.php',
		'IDataSource' => 'interfaces.php',
		'IDibiDriver' => 'interfaces.php',
		'IDibiReflector' => 'interfaces.php',
		'IDibiResultDriver' => 'interfaces.php',
	];
	if (isset($map[$class])) {
		require __DIR__ . '/Dibi/' . $map[$class];
	}
});
