<style>
pre.dibi { padding-bottom: 10px; }
</style>
<h1>dibi SQL builder example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';

// required since PHP 5.1.0
if (function_exists('date_default_timezone_set')) {
     date_default_timezone_set('Europe/Prague');
}


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));



$array1 = array(1, 2, 3);
$array2 = array('one', 'two', 'three');
$array3 = array(
    'col1' => 'one',
    'col2' => 'two',
    'col3' => 'three',
);
$array4 = array(
    'a'   => 12,
    'b'   => NULL,
    'c'   => dibi::datetime(),
    'd'   => 'any string',
);
$array5 = array('RAND()', '[col1] > [col2]');


dibi::test("
SELECT *
FROM [db.table]
WHERE ([test.a] LIKE %d", '1995-03-01', "
  OR [b1] IN (", $array1, ")
  OR [b2] IN (%s", $array1, ")
  OR [b3] IN (", $array2, ")
  OR [b4] IN (%n", $array3, ")
  OR [b5] IN (%sql", $array5, ")
  OR [b6] IN (", array(), ")
  AND [c] = 'embedded '' string'
  OR [d]=%i", 10.3, "
  OR [e]=%i", NULL, "
  OR [true]=", TRUE, "
  OR [false]=", FALSE, "
  OR [str_null]=%sn", '', "
  OR [str_not_null]=%sn", 'hello', "
LIMIT 10");


// dibi detects INSERT or REPLACE command
dibi::test("INSERT INTO [mytable]", $array4);


// dibi detects MULTI INSERT or REPLACE command
dibi::test("REPLACE INTO [mytable]", $array4, $array4, $array4);


// dibi detects UPDATE command
$n = 123;
dibi::test("UPDATE [mytable] SET", $array4, " WHERE [id]=%i", $n);


// array with modifier %a - assoc
dibi::test("UPDATE [mytable] SET%a", $array4, " WHERE [id]=%i", $n);


// long numbers
dibi::test("SELECT %i", '-123456789123456789123456789');

// long float numbers
dibi::test("SELECT %f", '-.12345678912345678912345678e10');

// hex numbers
dibi::test("SELECT %i", '0x11');

// invalid input
dibi::test("SELECT %s", (object) array(123), ', %m', 123);
