<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);
$translator = new Dibi\Translator($conn);

$dateinterval = new DateInterval('PT10H20M30S');

Assert::equal('10:20:30', $translator->formatValue($dateinterval, null));
