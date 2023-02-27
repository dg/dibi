<?php

/**
 * @dataProvider? ../databases.ini mysql
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new Dibi\Connection($config);

$conn->query('DROP TABLE IF EXISTS timetest');
$conn->query('CREATE TABLE timetest (col TIME NOT NULL) ENGINE=InnoDB');
$conn->query('INSERT INTO timetest VALUES ("12:30:40")');
Assert::equal(new DateInterval('PT12H30M40S'), $conn->fetchSingle('SELECT * FROM timetest'));
