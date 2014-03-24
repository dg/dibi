<?php

/**
 * Test: DateTimeInterface of DibiTranslator
 *
 * @author     Patrik Votoček
 * @phpversion 5.5
 */


require __DIR__ . '/bootstrap.php';

$connection = new DibiConnection(array(
	'driver' => 'sqlite3',
	'database' => ':memory:',
));
$translator = new DibiTranslator($connection);
$ref = new ReflectionProperty('DibiTranslator', 'driver');
$ref->setAccessible(true);
$ref->setValue($translator, $connection->getDriver());

$datetime = new DateTime('1978-01-23 00:00:00');

Assert::equal($datetime->format('U'), $translator->formatValue(new DateTime($datetime->format('c')), null));
Assert::equal($datetime->format('U'), $translator->formatValue(new DateTimeImmutable($datetime->format('c')), null));
