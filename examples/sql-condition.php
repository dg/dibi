<style>
pre.dibi { padding-bottom: 10px; }
</style>
<h1>dibi conditional SQL example</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));


$cond1 = rand(0,2) < 1;
$cond2 = rand(0,2) < 1;
$foo = -1;
$bar = 2;


$name = $cond1 ? 'K%' : NULL;

// if & end
dibi::test('
SELECT *
FROM [customers]
%if', isset($name), 'WHERE [name] LIKE %s', $name, '%end'
);


// if & else & (optional) end
dibi::test("
SELECT *
FROM [people]
WHERE [id] > 0
    %if", ($foo > 0), "AND [foo]=%i", $foo, "
    %else %if", ($bar > 0), "AND [bar]=%i", $bar, "
");


// nested condition
dibi::test('
SELECT *
FROM [customers]
WHERE
    %if', isset($name), '[name] LIKE %s', $name, '
        %if', $cond2, 'AND [admin]=1 %end
    %else 1 LIMIT 10 %end'
);
