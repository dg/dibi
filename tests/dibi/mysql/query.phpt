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
$translator = new DibiTranslator($connection);

// %s
$expected = "SELECT `username` FROM `users` WHERE `user` = 'admin'";
Assert::same($expected, $translator->translate(array("SELECT [username] FROM [users] WHERE [user] = %s", 'admin')));
$fluent = new DibiFluent($connection);
Assert::same($expected, (string)$fluent->select('username')->from('users')->where('[user] = %s', 'admin'));
unset($fluent);

// %i %u
$expected = "SELECT `username` FROM `users` WHERE `id` = 10 OR `id` = 11";
Assert::same($expected, $translator->translate(array("SELECT [username] FROM [users] WHERE [id] = %i OR [id] = %u", 10, 11)));
$fluent = new DibiFluent($connection);
Assert::same($expected, (string)$fluent->select('username')->from('users')->where('[id] = %i OR [id] = %u', 10, 11));
unset($fluent);

// %n
$expected = "SELECT `username` FROM `users` WHERE `id` = 10";
Assert::same($expected, $translator->translate(array("SELECT %n FROM %n WHERE %n = %i", 'username', 'users', 'id', 10)));
$fluent = new DibiFluent($connection);
Assert::same($expected, (string)$fluent->select('%n', 'username')->from('%n', 'users')->where('%n = %i', 'id', 10));
unset($fluent);

// %b
$expected = "SELECT `username` FROM `users` WHERE `active` = 1";
Assert::same($expected, $translator->translate(array("SELECT [username] FROM [users] WHERE [active] = %b", TRUE)));
$fluent = new DibiFluent($connection);
Assert::same($expected, (string)$fluent->select('username')->from('users')->where('[active] = %b', TRUE));
unset($fluent);
