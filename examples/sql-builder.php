<pre>
<?php

require_once '../dibi/dibi.php';


// mysql
dibi::connect(array(
    'driver'   => 'mysqli',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '***',
    'database' => 'test',
    'charset'  => 'utf8',
));


$arr1 = array(1, 2, 3);
$arr2 = array('one', 'two', 'three');
$arr3 = array(
    'a' => 'one',
    'b' => 'two',
    'c' => 'three',
);
$arr4 = array(
    'A' => 12,
    'B' => NULL,
    'C' => new TDateTime(31542),
    'D' => 'string',
);

dibi::test(
"
SELECT *
FROM [test]
WHERE ([test.a] LIKE %T", '1995-03-01', "
  OR [b1] IN (", $arr1, ")
  OR [b2] IN (", $arr2, ")
  OR [b3] IN (%N", $arr3, ")
  OR [b4] IN %V", $arr4, "
  AND [c] = 'embedded '' string'
  OR [d]=%d", 10.3, "
  OR [true]=", true, "
  OR [false]=", false, "
  OR [null]=", NULL, "
LIMIT 10");

?>
