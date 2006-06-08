<style>
pre.dibi { padding-bottom: 10px; }
</style>
<pre>
<?php

require_once '../dibi/dibi.php';


// mysql
dibi::connect(array(
    'driver'   => 'mysqli',
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
    'c%?' => NULL,
    'd%T' => time(),  // modifier 'T' means datetime
    'e'   => 'any string',
);
$arr5 = array('RAND()', '[col1] > [col2]');


dibi::test("
SELECT *
FROM [db.table]
WHERE ([test.a] LIKE %D", '1995-03-01', "
  OR [b1] IN (", $arr1, ")   
  OR [b2] IN (%s", $arr1, ")
  OR [b3] IN (", $arr2, ")
  OR [b4] IN (%n", $arr3, ")
  OR [b4] IN (%p", $arr5, ")
  AND [c] = 'embedded '' string'
  OR [d]=%d", 10.3, "
  OR [true]=", true, "
  OR [false]=", false, "
  OR [null]=", NULL, "
LIMIT 10");


// dibi detects INSERT or REPLACE command
dibi::test("INSERT INTO [test]", $arr4);  


// dibi detects UPDATE command
$n = 123;
dibi::test("UPDATE [test] SET", $arr4, " WHERE [id]=%n", $n);  


// array with modifier %d - means strings
dibi::test("UPDATE [test] SET%s", $arr4, " WHERE [id]=%n", $n);  

?>
