<?php

/**
 * Test: Cloning of DibiFluent
 *
 * @author     David Grudl
 * @category   Dibi
 * @subpackage UnitTests
 */



require dirname(__FILE__) . '/initialize.php';



dibi::connect($config['sqlite']);


$fluent = new DibiFluent(dibi::getConnection());
$fluent->select('*')->from('table')->where('x=1');
$dolly = clone $fluent;
$dolly->where('y=1');
$dolly->clause('FOO');

$fluent->test();
$dolly->test();



$fluent = dibi::select('id')->from('table')->where('id = %i',1);
$dolly = clone $fluent;
$dolly->where('cd = %i',5);

$fluent->test();
$dolly->test();



$fluent = dibi::select("*")->from("table");
$dolly = clone $fluent;
$dolly->removeClause("select")->select("count(*)");

$fluent->test();
$dolly->test();



__halt_compiler() ?>

------EXPECT------
SELECT *
FROM [table]
WHERE x=1

SELECT *
FROM [table]
WHERE x=1 AND y=1 FOO

SELECT [id]
FROM [table]
WHERE id = 1

SELECT [id]
FROM [table]
WHERE id = 1 AND cd = 5

SELECT *
FROM [table]

SELECT count(*)
FROM [table]
