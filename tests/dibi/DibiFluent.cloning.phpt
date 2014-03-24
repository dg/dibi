<?php

/**
 * Test: Cloning of DibiFluent
 *
 * @author     David Grudl
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


dibi::connect($config['sqlite3']);


$fluent = new DibiFluent(dibi::getConnection());
$fluent->select('*')->from('table')->where('x=1');
$dolly = clone $fluent;
$dolly->where('y=1');
$dolly->clause('FOO');

Assert::same( 'SELECT * FROM [table] WHERE x=1', (string) $fluent );
Assert::same( 'SELECT * FROM [table] WHERE x=1 AND y=1 FOO', (string) $dolly );


$fluent = dibi::select('id')->from('table')->where('id = %i',1);
$dolly = clone $fluent;
$dolly->where('cd = %i',5);

Assert::same( 'SELECT [id] FROM [table] WHERE id = 1', (string) $fluent );
Assert::same( 'SELECT [id] FROM [table] WHERE id = 1 AND cd = 5', (string) $dolly );


$fluent = dibi::select("*")->from("table");
$dolly = clone $fluent;
$dolly->removeClause("select")->select("count(*)");

Assert::same( 'SELECT * FROM [table]', (string) $fluent );
Assert::same( 'SELECT count(*) FROM [table]', (string) $dolly );
