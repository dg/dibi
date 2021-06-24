<?php

declare(strict_types=1);

use Dibi\DateTime;
use Tester\Assert;

require __DIR__ . '/bootstrap.php';

date_default_timezone_set('Europe/Prague');

Assert::same('1978-01-23 11:40:00.000000', (string) new DateTime(254400000));
Assert::same('1978-01-23 11:40:00.000000', (string) (new DateTime)->setTimestamp(254400000));
Assert::same(254400000, (new DateTime(254400000))->getTimestamp());

Assert::same('2050-08-13 11:40:00.000000', (string) new DateTime(2544000000));
if (is_int(2544000000)) {
	Assert::same('2050-08-13 11:40:00.000000', (string) (new DateTime)->setTimestamp(2544000000)); // 64 bit only
}
Assert::same(is_int(2544000000) ? 2544000000 : false, (new DateTime(2544000000))->getTimestamp()); // 64 bit

Assert::same('1978-05-05 00:00:00.000000', (string) new DateTime('1978-05-05'));
