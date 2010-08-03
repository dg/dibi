<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Query Language & Conditions | dibi</h1>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));


// some variables
$cond1 = TRUE;
$cond2 = FALSE;
$foo = -1;
$bar = 2;

// conditional variable
$name = $cond1 ? 'K%' : NULL;

// if & end
dibi::test('
	SELECT *
	FROM customers
	%if', isset($name), 'WHERE name LIKE %s', $name, '%end'
);
// -> SELECT * FROM customers WHERE name LIKE 'K%'




// if & else & (optional) end
dibi::test("
	SELECT *
	FROM people
	WHERE id > 0
		%if", ($foo > 0), "AND foo=%i", $foo, "
		%else %if", ($bar > 0), "AND bar=%i", $bar, "
");
// -> SELECT * FROM people WHERE id > 0 AND bar=2



// nested condition
dibi::test('
	SELECT *
	FROM customers
	WHERE
		%if', isset($name), 'name LIKE %s', $name, '
			%if', $cond2, 'AND admin=1 %end
		%else 1 LIMIT 10 %end'
);
// -> SELECT * FROM customers WHERE LIMIT 10
