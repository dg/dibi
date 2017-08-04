<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$hash = new Dibi\HashMap(function ($v) {
	return "b-$v-e";
});

Assert::same('b-X-e', $hash->{'X'});
Assert::same('b--e', $hash->{''});
