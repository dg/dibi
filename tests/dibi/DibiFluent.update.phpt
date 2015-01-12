<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new DibiConnection($config);


$arr = array(
	'title'   => 'Super Product',
	'price'   => 12,
	'brand'   => NULL,
);

$fluent = $conn->update('table', $arr)
	->setFlag('IGNORE')->setFlag('DELAYED');

Assert::same(
	reformat('UPDATE IGNORE DELAYED [table] SET [title]=\'Super Product\', [price]=12, [brand]=NULL'),
	(string) $fluent
);

$fluent->set(array('another' => 123));

Assert::same(
	reformat('UPDATE IGNORE DELAYED [table] SET [title]=\'Super Product\', [price]=12, [brand]=NULL , [another]=123'),
	(string) $fluent
);
