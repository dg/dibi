<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$translator = new Dibi\Translator($conn);

switch ($config['system']) {
	default:
		Assert::exception(function () use ($translator) {
			$translator->formatValue(new DateInterval('PT10H20M30S'), null);
		}, Dibi\Exception::class);
}
