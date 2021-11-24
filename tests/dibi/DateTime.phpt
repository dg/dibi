<?php

declare(strict_types=1);

use Dibi\DateTime;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';


date_default_timezone_set('Europe/Prague');

Assert::same('1978-01-23 11:40:00.000000', (string) new DateTime(254_400_000));
Assert::same('1978-01-23 11:40:00.000000', (string) (new DateTime)->setTimestamp(254_400_000));
Assert::same(254_400_000, (new DateTime(254_400_000))->getTimestamp());

Assert::same(is_int(2_544_000_000) ? 2_544_000_000 : '2544000000', (new DateTime(2_544_000_000))->getTimestamp()); // 64 bit

Assert::same('1978-05-05 00:00:00.000000', (string) new DateTime('1978-05-05'));
