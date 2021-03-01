<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$translator = new Dibi\Translator($conn);

switch ($config['system']) {
	case 'mysql':
		Assert::equal('10:20:30.0', $translator->formatValue(new DateInterval('PT10H20M30S'), null));
		Assert::equal('-1:00:00.0', $translator->formatValue(DateInterval::createFromDateString('-1 hour'), null));
		Assert::exception(
			fn() => $translator->formatValue(new DateInterval('P2Y4DT6H8M'), null),
			Dibi\NotSupportedException::class,
			'Only time interval is supported.',
		);
		break;

	default:
		Assert::exception(
			fn() => $translator->formatValue(new DateInterval('PT10H20M30S'), null),
			Dibi\Exception::class,
		);
}
