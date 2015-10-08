<?php

/**
 * @dataProvider ../databases.ini
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);


Assert::same(
	reformat('SELECT * FROM where WHERE select < 2'),
	$conn->translate('SELECT * FROM where WHERE select < 2')
);


Assert::same(
	reformat('SELECT * FROM [where] WHERE where.select < 2'),
	$conn->translate('SELECT * FROM [where] WHERE where.select < 2')
);


Assert::same(
	reformat('SELECT * FROM [where] WHERE [where].[select] < 2'),
	$conn->translate('SELECT * FROM [where] WHERE [where.select] < 2')
);


Assert::same(
	reformat('SELECT * FROM [where] as [temp] WHERE [temp].[select] < 2'),
	$conn->translate('SELECT * FROM [where] as [temp] WHERE [temp.select] < 2')
);


Assert::same(
	reformat('SELECT * FROM [where] WHERE [quot\'n\' space] > 2'),
	$conn->translate("SELECT * FROM [where] WHERE [quot'n' space] > 2")
);


Assert::same(
	reformat('SELECT * FROM [where] WHERE [where].[quot\'n\' space] > 2'),
	$conn->translate("SELECT * FROM [where] WHERE [where.quot'n' space] > 2")
);
