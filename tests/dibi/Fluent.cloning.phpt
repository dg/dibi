<?php

use Tester\Assert;
use Dibi\Fluent;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);

$fluent = new Fluent($conn);
$fluent->select('*')->from('table')->where('x=1');
$dolly = clone $fluent;
$dolly->where('y=1');
$dolly->clause('FOO');

Assert::same(reformat('SELECT * FROM [table] WHERE x=1'), (string) $fluent);
Assert::same(reformat('SELECT * FROM [table] WHERE x=1 AND y=1 FOO'), (string) $dolly);


$fluent = new Fluent($conn);
$fluent->select('id')->from('table')->where('id = %i', 1);
$dolly = clone $fluent;
$dolly->where('cd = %i', 5);

Assert::same(reformat('SELECT [id] FROM [table] WHERE id = 1'), (string) $fluent);
Assert::same(reformat('SELECT [id] FROM [table] WHERE id = 1 AND cd = 5'), (string) $dolly);


$fluent = new Fluent($conn);
$fluent->select('*')->from('table');
$dolly = clone $fluent;
$dolly->removeClause('select')->select('count(*)');

Assert::same(reformat('SELECT * FROM [table]'), (string) $fluent);
Assert::same(reformat('SELECT count(*) FROM [table]'), (string) $dolly);
