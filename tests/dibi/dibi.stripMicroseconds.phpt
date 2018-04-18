<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$dt = new DateTime('2018-04-18 13:40:09.123456');

$res = dibi::stripMicroseconds($dt);
Assert::same('2018-04-18 13:40:09.123456', $dt->format('Y-m-d H:i:s.u'));
Assert::same('2018-04-18 13:40:09.000000', $res->format('Y-m-d H:i:s.u'));
