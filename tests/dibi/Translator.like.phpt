<?php

/**
 * @dataProvider ../databases.ini  !=sqlsrv
 */

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';


$conn = new Dibi\Connection($config);

// starts with
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'a', 'b'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'baa', 'aa'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', 'aab', 'aa'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'bba', '%a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', '%ba', '%a'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', '%ab', '%a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'aa', '_a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', '_b', '_a'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', '_ab', '_a'));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', 'a"a', 'a"'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'b"', '%"'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', '%"', '%"'));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', "a'a", "a'"));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', "b'", "%'"));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', "%'", "%'"));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', 'a\\a', 'a\\'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'b\\', '%\\'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', '%\\', '%\\'));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', 'a[a', 'a['));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like~', 'b[', '%['));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like~', '%[', '%['));


// ends with
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', 'a', 'b'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', 'baa', 'aa'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', 'aab', 'aa'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', 'bba', '%a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', 'a%b', '%a'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', 'b%a', '%a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', 'aa', '_a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', '_b', '_a'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', 'b_a', '_a'));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', 'a"a', '"a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', '"b', '"%'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', '"%', '"%'));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', "a'a", "'a"));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', "'b", "'%"));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', "'%", "'%"));

Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', 'a\\a', '\\a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like', '\\b', '\\%'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like', '\\%', '\\%'));


// contains
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like~', 'a', 'b'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like~', 'baa', 'aa'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like~', 'aab', 'aa'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %~like~', 'bba', '%a'));
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %~like~', 'b%a', '%a'));


// matches
Assert::truthy($conn->fetchSingle('SELECT ? LIKE %like', 'a', 'a'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like', 'a', 'aa'));
Assert::falsey($conn->fetchSingle('SELECT ? LIKE %like', 'a', 'b'));
