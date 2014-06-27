<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Query Language & Conditions | dibi</h1>

<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
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
	%if', isset($name), 'WHERE name LIKE ?', $name, '%end'
);
// -> SELECT * FROM customers WHERE name LIKE 'K%'


// if & else & (optional) end
dibi::test("
	SELECT *
	FROM people
	WHERE id > 0
		%if", ($foo > 0), "AND foo=?", $foo, "
		%else %if", ($bar > 0), "AND bar=?", $bar, "
");
// -> SELECT * FROM people WHERE id > 0 AND bar=2


// nested condition
dibi::test('
	SELECT *
	FROM customers
	WHERE
		%if', isset($name), 'name LIKE ?', $name, '
			%if', $cond2, 'AND admin=1 %end
		%else 1 LIMIT 10 %end'
);
// -> SELECT * FROM customers WHERE LIMIT 10


// IF()
dibi::test('UPDATE products SET', array(
	'price' => array('IF(price_fixed, price, ?)', 123),
));
// -> SELECT * FROM customers WHERE LIMIT 10
