<style>
pre.dibi { padding-bottom: 10px; }
</style>
<pre>
<?php

require_once '../dibi/dibi.php';

// required since PHP 5.1.0
if (function_exists('date_default_timezone_set'))
     date_default_timezone_set('Europe/Prague'); // or 'GMT'


// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'charset'  => 'utf8',
));



$arr1 = array(1, 2, 3);
$arr2 = array('one', 'two', 'three');
$arr3 = array(
    'col1' => 'one',
    'col2' => 'two',
    'col3' => 'three',
);
$arr4 = array(
    'a'   => 12,
    'b'   => NULL,
    'c%t' => time(),  // modifier 'T' means datetime
    'd'   => 'any string',
);
$arr5 = array('RAND()', '[col1] > [col2]');


dibi::test("
SELECT *
FROM [db.table]
WHERE ([test.a] LIKE %d", '1995-03-01', "
  OR [b1] IN (", $arr1, ")
  OR [b2] IN (%s", $arr1, ")
  OR [b3] IN (", $arr2, ")
  OR [b4] IN (%n", $arr3, ")
  OR [b5] IN (%sql", $arr5, ")
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
dibi::test("INSERT INTO [test]", $arr4);


// dibi detects UPDATE command
$n = 123;
dibi::test("UPDATE [test] SET", $arr4, " WHERE [id]=%i", $n);


// array with modifier %a - assoc
dibi::test("UPDATE [test] SET%a", $arr4, " WHERE [id]=%i", $n);



