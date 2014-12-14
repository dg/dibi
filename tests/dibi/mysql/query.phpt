<?php

/**
 * Test: MySQL Queries
 *
 * @author     Honza Cerny
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$options = $config['mysql'];
$options['lazy'] = TRUE;

$connection = new DibiConnection($options);

// %s
$SQL = $connection->testQuery("SELECT [username] FROM [users] WHERE [user] = %s", 'admin');
Assert::same("SELECT `username` FROM `users` WHERE `user` = 'admin'", $SQL);

// %i
$SQL = $connection->testQuery("SELECT [username] FROM [users] WHERE [id] = %i", 10);
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10", $SQL);

// %n
$SQL = $connection->testQuery("SELECT %n FROM %n WHERE %n = %i", 'username', 'users', 'id', 10);
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10", $SQL);

// %b
$SQL = $connection->testQuery("SELECT [username] FROM [users] WHERE [active] = %b", TRUE);
Assert::same("SELECT `username` FROM `users` WHERE `active` = 1", $SQL);