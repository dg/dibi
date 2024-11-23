<?php declare(strict_types=1);

namespace Dibi;

use JetBrains\PhpStorm\Language;

interface IConnection
{
	public function connect(): void;

	public function disconnect(): void;

	public function isConnected(): bool;

	public function getDriver(): Drivers\Connection;

	public function query(#[Language('GenericSQL')] mixed ...$args): Result;

	public function translate(#[Language('GenericSQL')] mixed ...$args): string;

	public function test(#[Language('GenericSQL')] mixed ...$args): bool;

	public function dataSource(#[Language('GenericSQL')] mixed ...$args): DataSource;

	public function nativeQuery(#[Language('SQL')] string $sql): Result;

	public function getAffectedRows(): int;

	public function getInsertId(?string $sequence = null): int;

	public function begin(?string $savepoint = null): void;

	public function commit(?string $savepoint = null): void;

	public function rollback(?string $savepoint = null): void;

	public function createResultSet(Drivers\Result $resultDriver): Result;

	public function substitute(string $value): string;
}
