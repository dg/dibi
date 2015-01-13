<?php

/**
 * Test: MySQL Queries
 *
 * @author     Honza Cerny
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$connection = new DibiConnection($config);
$translator = new DibiTranslator($connection);

if ($config['system'] !== 'mysql') {
	Tester\Environment::skip('Not supported.');
}

// %s
$sql = $translator->translate(array("SELECT [username] FROM [users] WHERE [user] = %s", 'admin'));
Assert::same("SELECT `username` FROM `users` WHERE `user` = 'admin'", $sql);

// %i %u
$sql = $translator->translate(array("SELECT [username] FROM [users] WHERE [id] = %i OR [id] = %u", 10, 11));
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10 OR `id` = 11", $sql);

// %n
$sql = $translator->translate(array("SELECT %n FROM %n WHERE %n = %i", 'username', 'users', 'id', 10));
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10", $sql);

// %b
$sql = $translator->translate(array("SELECT [username] FROM [users] WHERE [active] = %b", TRUE));
Assert::same("SELECT `username` FROM `users` WHERE `active` = 1", $sql);
