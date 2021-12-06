<?php

/**
 * @phpVersion 8.1
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$translator = new Dibi\Translator($conn);


enum EnumInt: int
{
	case One = 1;
}

enum EnumString: string
{
	case One = 'one';
}

enum PureEnum
{
	case One;
}


Assert::equal('1', $translator->formatValue(EnumInt::One, null));

Assert::equal(match ($config['driver']) {
	'sqlsrv' => "N'one'",
	default => "'one'",
}, $translator->formatValue(EnumString::One, null));

Assert::equal('**Unexpected PureEnum**', $translator->formatValue(PureEnum::One, null));
