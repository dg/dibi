<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$translator = new Dibi\Translator($conn);

$datetime = new DateTime('1978-01-23 00:00:00');

Assert::equal($datetime->format('U'), $translator->formatValue(new DateTime($datetime->format('c')), null));
Assert::equal($datetime->format('U'), $translator->formatValue(new DateTimeImmutable($datetime->format('c')), null));
