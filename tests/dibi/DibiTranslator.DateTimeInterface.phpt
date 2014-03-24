<?php

/**
 * Test: DateTimeInterface of DibiTranslator
 *
 * @author     Patrik Votoček
 * @phpversion 5.5
 */

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$connection = new DibiConnection(array(
	'driver' => 'sqlite3',
	'database' => ':memory:',
));
$translator = new DibiTranslator($connection);

$datetime = new DateTime('1978-01-23 00:00:00');

Assert::equal($datetime->format('U'), $translator->formatValue(new DateTime($datetime->format('c')), NULL));
Assert::equal($datetime->format('U'), $translator->formatValue(new DateTimeImmutable($datetime->format('c')), NULL));
