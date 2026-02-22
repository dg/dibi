<?php declare(strict_types=1);

/**
 * PHPStan type tests for Dibi.
 */

use Dibi\Connection;
use Dibi\Fluent;
use Dibi\Row;
use function PHPStan\Testing\assertType;


/** @param callable(Connection): int $cb */
function testTransactionInt(Connection $conn, callable $cb): void
{
	$result = $conn->transaction($cb);
	assertType('int', $result);
}


/** @param callable(Connection): Row $cb */
function testTransactionRow(Connection $conn, callable $cb): void
{
	$result = $conn->transaction($cb);
	assertType('Dibi\Row', $result);
}


function testFluentExecuteDefault(Fluent $fluent): void
{
	assertType('Dibi\Result|null', $fluent->execute());
}


function testFluentExecuteIdentifier(Fluent $fluent): void
{
	assertType('int|null', $fluent->execute(Fluent::Identifier));
}


function testFluentExecuteAffectedRows(Fluent $fluent): void
{
	assertType('int|null', $fluent->execute(Fluent::AffectedRows));
}


function testFluentFetch(Fluent $fluent): void
{
	assertType('array<mixed>|Dibi\Row|null', $fluent->fetch());
}


function testConnectionFetch(Connection $conn): void
{
	assertType('Dibi\Row|null', $conn->fetch('SELECT 1'));
}
