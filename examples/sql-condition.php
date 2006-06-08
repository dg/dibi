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


$cond1 = rand(0,2) < 1;
$cond2 = rand(0,2) < 1;


$user = $cond1 ? 'Davidek' : NULL;


dibi::test('
SELECT *
FROM [table]
%if', isset($user), 'WHERE [user]=%s', $user, '%end'
);


// last end is optional
dibi::test('
SELECT *
FROM %if', $cond1, '[one_table] %else [second_table]'
);


// nested condition
dibi::test('
SELECT *
FROM [test]
WHERE
    %if', isset($user), '[user]=%s', $user, '
        %if', $cond2, 'AND [admin]=1 %end
    %else LIMIT 10 %end'
);

?>
