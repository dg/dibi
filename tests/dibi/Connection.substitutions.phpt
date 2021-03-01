<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);

// create new substitution :blog:  ==>  wp_
$conn->getSubstitutes()->blog = 'wp_';

Assert::same(
	reformat('UPDATE wp_items SET [val]=1'),
	$conn->translate('UPDATE :blog:items SET [val]=1'),
);

Assert::same(
	reformat('UPDATE [wp_items] SET [val]=1'),
	$conn->translate('UPDATE [:blog:items] SET [val]=1'),
);

Assert::same(
	reformat("UPDATE 'wp_' SET [val]=1"),
	$conn->translate('UPDATE :blog: SET [val]=1'),
);

Assert::same(
	reformat("UPDATE ':blg:' SET [val]=1"),
	$conn->translate('UPDATE :blg: SET [val]=1'),
);

Assert::same(
	reformat("UPDATE table SET [text]=':blog:a'"),
	$conn->translate("UPDATE table SET [text]=':blog:a'"),
);


// create new substitution :: (empty)  ==>  my_
$conn->getSubstitutes()->{''} = 'my_';

Assert::same(
	reformat('UPDATE my_table SET [val]=1'),
	$conn->translate('UPDATE ::table SET [val]=1'),
);


// create substitutions using fallback callback
$conn->getSubstitutes()->setCallback(fn($expr) => '_' . $expr . '_');

Assert::same(
	reformat('UPDATE _account_user SET [val]=1'),
	$conn->translate('UPDATE :account:user SET [val]=1'),
);
