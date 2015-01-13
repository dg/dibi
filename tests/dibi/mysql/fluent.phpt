<?php

/**
 * Test: MySQL Fluent
 *
 * @author     Honza Cerny
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$connection = new DibiConnection($config);
$translator = new DibiTranslator($connection);


// %s
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[user] = %s', 'admin');
Assert::same(reformat("SELECT [username] FROM [users] WHERE [user] = 'admin'"), $sql);
unset($fluent);

// %i %u
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[id] = %i OR [id] = %u', 10, 11);
Assert::same(reformat("SELECT [username] FROM [users] WHERE [id] = 10 OR [id] = 11"), $sql);
unset($fluent);

// %n
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('%n', 'username')->from('%n', 'users')->where('%n = %i', 'id', 10);
Assert::same(reformat("SELECT [username] FROM [users] WHERE [id] = 10"), $sql);
unset($fluent);

// %b
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[active] = %b', TRUE);
Assert::same(reformat("SELECT [username] FROM [users] WHERE [active] = 1"), $sql);
unset($fluent);
