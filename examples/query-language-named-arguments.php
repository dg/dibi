<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Query Language Named Arguments Examples | dibi</h1>

<?php

require __DIR__ . '/../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver' => 'sqlite3',
	'database' => 'data/sample.s3db',
));
dibi::getConnection()->getSubstitutes()->test = 'test_';
dibi::getConnection()->getSubstitutes()->{''} = 'testtoo_';

// SELECT
$name = 'K%';
$timestamp = mktime(0, 0, 0, 10, 13, 1997);
$id_list = array(1, 2, 3);

// If the argument implements IDibiArguments, it is removed from the arguments
// and added to the named argument collections.
// If the %nmd modifier is used for a positional argument, it is also added
// to the named argument collections. It should be an array or object implementing
// the ArrayAccess interface.
// Any named argument is a colon with identifier with optional percent sign and
// value modifier.
// Original substitutions are still present.

dibi::test('
	SELECT COUNT(*) as [count]
	%nmd
	FROM [:test:customers]
	WHERE [name] LIKE :name
	AND [added] > :timestamp%d
	OR [customer_id] IN :id_list%in
	ORDER BY [name]'
, new ArrayObject(['timestamp' => new DibiDateTime($timestamp)])
, new DibiArguments(['name' => $name, 'id_list' => $id_list]));
