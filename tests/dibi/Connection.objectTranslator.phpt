<?php

/**
 * @dataProvider ../databases.ini
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config + ['formatDateTime' => "'Y-m-d H:i:s.u'", 'formatDate' => "'Y-m-d'"]);


class Email
{
	public $address = 'address@example.com';
}

class Time extends DateTimeImmutable
{
}


test('Without object translator', function () use ($conn) {
	Assert::exception(
		fn() => $conn->translate('?', new Email),
		Dibi\Exception::class,
		'SQL translate error: Unexpected Email',
	);
});


test('Basics', function () use ($conn) {
	$conn->setObjectTranslator(fn(Email $email) => new Dibi\Expression('?', $email->address));
	Assert::same(
		reformat([
			'sqlsrv' => "N'address@example.com'",
			"'address@example.com'",
		]),
		$conn->translate('?', new Email),
	);
});


test('DateTime', function () use ($conn) {
	$stamp = Time::createFromFormat('Y-m-d H:i:s', '2022-11-22 12:13:14');

	// Without object translator, DateTime child is translated by driver
	Assert::same(
		$conn->getDriver()->escapeDateTime($stamp),
		$conn->translate('?', $stamp),
	);


	// With object translator
	$conn->setObjectTranslator(fn(Time $time) => new Dibi\Expression('OwnTime(?)', $time->format('H:i:s')));
	Assert::same(
		reformat([
			'sqlsrv' => "OwnTime(N'12:13:14')",
			"OwnTime('12:13:14')",
		]),
		$conn->translate('?', $stamp),
	);


	// With modifier, it is still translated by driver
	Assert::same(
		$conn->getDriver()->escapeDateTime($stamp),
		$conn->translate('%dt', $stamp),
	);
	Assert::same(
		$conn->getDriver()->escapeDateTime($stamp),
		$conn->translate('%t', $stamp),
	);
	Assert::same(
		$conn->getDriver()->escapeDate($stamp),
		$conn->translate('%d', $stamp),
	);


	// DateTimeImmutable as a Time parent is not affected and still translated by driver
	$dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2022-11-22 12:13:14');
	Assert::same(
		$conn->getDriver()->escapeDateTime($dt),
		$conn->translate('?', $dt),
	);

	// But DateTime translation can be overloaded
	$conn->setObjectTranslator(fn(DateTimeInterface $dt) => new Dibi\Expression('OwnDateTime'));
	Assert::same(
		'OwnDateTime',
		$conn->translate('?', $dt),
	);
});


test('Complex structures', function () use ($conn) {
	$conn->setObjectTranslator(fn(Email $email) => new Dibi\Expression('?', $email->address));
	$conn->setObjectTranslator(fn(Time $time) => new Dibi\Expression('OwnTime(?)', $time->format('H:i:s')));
	$conn->setObjectTranslator(fn(DateTimeInterface $dt) => new Dibi\Expression('OwnDateTime'));

	$time = Time::createFromFormat('Y-m-d H:i:s', '2022-11-22 12:13:14');
	Assert::same(
		reformat([
			'sqlsrv' => "([a], [b], [c], [d], [e], [f], [g]) VALUES (OwnTime(N'12:13:14'), '2022-11-22', CONVERT(DATETIME2(7), '2022-11-22 12:13:14.000000'), CONVERT(DATETIME2(7), '2022-11-22 12:13:14.000000'), N'address@example.com', OwnDateTime, OwnDateTime)",
			'odbc' => "([a], [b], [c], [d], [e], [f], [g]) VALUES (OwnTime('12:13:14'), #11/22/2022#, #11/22/2022 12:13:14.000000#, #11/22/2022 12:13:14.000000#, 'address@example.com', OwnDateTime, OwnDateTime)",
			"([a], [b], [c], [d], [e], [f], [g]) VALUES (OwnTime('12:13:14'), '2022-11-22', '2022-11-22 12:13:14.000000', '2022-11-22 12:13:14.000000', 'address@example.com', OwnDateTime, OwnDateTime)",
		]),
		$conn->translate('%v', [
			'a' => $time,
			'b%d' => $time,
			'c%t' => $time,
			'd%dt' => $time,
			'e' => new Email,
			'f' => new DateTime,
			'g' => new DateTimeImmutable,
		]),
	);
});


test('Invalid translator', function () use ($conn) {
	Assert::exception(
		fn() => $conn->setObjectTranslator(fn($email) => 'foo'),
		Dibi\Exception::class,
		'Object translator must have exactly one parameter with class typehint.',
	);

	Assert::exception(
		fn() => $conn->setObjectTranslator(fn(string $email) => 'foo'),
		Dibi\Exception::class,
		"Object translator must have exactly one parameter with non-nullable class typehint, got 'string'.",
	);

	Assert::exception(
		fn() => $conn->setObjectTranslator(fn(Email|bool $email) => 'foo'),
		Dibi\Exception::class,
		"Object translator must have exactly one parameter with non-nullable class typehint, got 'bool'.",
	);

	Assert::exception(
		fn() => $conn->setObjectTranslator(fn(Email|null $email) => 'foo'),
		Dibi\Exception::class,
		"Object translator must have exactly one parameter with non-nullable class typehint, got '?Email'.",
	);

	$conn->setObjectTranslator(fn(Email $email) => 'foo');
	Assert::exception(
		fn() => $conn->translate('?', new Email),
		Dibi\Exception::class,
		"Object translator for class 'Email' returned 'string' but Dibi\\Expression expected.",
	);
});
