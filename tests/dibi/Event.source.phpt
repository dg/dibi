<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);

$event = new Dibi\Event($conn, Dibi\Event::CONNECT);
Assert::same([__FILE__, __LINE__ - 1], $event->source);

eval('$event = new Dibi\Event($conn, Dibi\Event::CONNECT);');
Assert::same([__FILE__, __LINE__ - 1], $event->source);

array_map(function () use ($conn) {
	$event = new Dibi\Event($conn, Dibi\Event::CONNECT);
	Assert::same([__FILE__, __LINE__ - 1], $event->source);
}, [null]);
