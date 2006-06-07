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


$user = NULL;
// or
$user = 'Jesus';


dibi::test('
SELECT *
FROM [test]
WHERE %if', isset($user), 'user=%s', $user, '%end' // last end is optional
);


$cond = rand(0,2) < 1; 

dibi::test('
SELECT *
FROM %if', $cond, '[one_table]', '%else', '[second_table]', '%end'
);


// shorter way
dibi::test('
SELECT *
FROM %if', $cond, '[one_table] %else', '[second_table] %end'
);


// nested condition
dibi::test('
SELECT *
FROM [test]
WHERE 
    %if', isset($user), 'user=%s', $user, '
        %if', $cond, 'AND [admin]=1 %end', '
    AND [visible]=1 %end'
);

?>
