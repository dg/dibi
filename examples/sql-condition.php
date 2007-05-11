<style>
pre.dibi { padding-bottom: 10px; }
</style>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));


$cond1 = rand(0,2) < 1;
$cond2 = rand(0,2) < 1;


$name = $cond1 ? 'K%' : NULL;

// if & end
dibi::test('
SELECT *
FROM [customers]
%if', isset($name), 'WHERE [name] LIKE %s', $name, '%end'
);


// if & else & end (last end is optional)
dibi::test('
SELECT *
FROM %if', $cond1, '[customers] %else [products]'
);


// nested condition
dibi::test('
SELECT *
FROM [customers]
WHERE
    %if', isset($name), '[name] LIKE %s', $name, '
        %if', $cond2, 'AND [admin]=1 %end
    %else LIMIT 10 %end'
);
