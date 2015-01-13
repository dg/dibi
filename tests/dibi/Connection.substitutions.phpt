<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new DibiConnection($config);

// create new substitution :blog:  ==>  wp_
$conn->getSubstitutes()->blog = 'wp_';

Assert::same(
	reformat('UPDATE wp_items SET [text]=\'Hello World\''),
	$conn->translate("UPDATE :blog:items SET [text]='Hello World'")
);

Assert::same(
	reformat('UPDATE \'wp_\' SET [text]=\'Hello World\''),
	$conn->translate("UPDATE :blog: SET [text]='Hello World'")
);

Assert::same(
	reformat('UPDATE \':blg:\' SET [text]=\'Hello World\''),
	$conn->translate("UPDATE :blg: SET [text]='Hello World'")
);
