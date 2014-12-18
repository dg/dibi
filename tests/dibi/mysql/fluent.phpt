<?php

/**
 * Test: MySQL Fluent
 *
 * @author     Honza Cerny
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$options = $config['mysql'];
$options['lazy'] = TRUE;

$connection = new DibiConnection($options);

// %s
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[user] = %s', 'admin');
Assert::same("SELECT `username` FROM `users` WHERE `user` = 'admin'", $sql);
unset($fluent);

// %i %u
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[id] = %i OR [id] = %u', 10, 11);
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10 OR `id` = 11", $sql);
unset($fluent);

// %n
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('%n', 'username')->from('%n', 'users')->where('%n = %i', 'id', 10);
Assert::same("SELECT `username` FROM `users` WHERE `id` = 10", $sql);
unset($fluent);

// %b
$fluent = new DibiFluent($connection);
$sql = (string)$fluent->select('username')->from('users')->where('[active] = %b', TRUE);
Assert::same("SELECT `username` FROM `users` WHERE `active` = 1", $sql);
unset($fluent);
