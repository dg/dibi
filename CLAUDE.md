# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Dibi** is a PHP database abstraction library (DBAL) providing a unified interface for multiple database systems (MySQL, PostgreSQL, SQLite, Oracle, Firebird, ODBC). It is a standalone library, not an application.

- **PHP Version**: 8.2 - 8.5
- **Homepage**: https://dibi.nette.org
- **Author**: David Grudl (Nette framework creator)

## Essential Commands

### Development Workflow

```bash
# Run all tests
composer run tester

# Run specific test file
vendor/bin/tester tests/dibi/DataSource.phpt -s -C

# Run tests in a directory
vendor/bin/tester tests/dibi/ -s -C

# Static analysis (PHPStan Level 5)
composer run phpstan
```

### Installation

```bash
composer install
```

## Architecture Overview

### Core Components

**Connection Layer** (`src/Dibi/Connection.php`):
- Main entry point for all database operations
- Manages connection lifecycle, transactions, and query execution
- Event system for profiling/logging
- Lazy connection initialization

**Driver Architecture** (`src/Dibi/Drivers/`):
- 27+ driver implementations (MySqli, PDO, PostgreSQL, SQLite3, Oracle, Firebird, ODBC, etc.)
- Each driver implements: `Driver`, `ResultDriver`, and `Reflector` interfaces
- Handles database-specific SQL escaping, type conversion, and LIMIT/OFFSET injection
- Key files: `MySqliDriver.php`, `PdoDriver.php`, `PostgreDriver.php`, `Sqlite3Driver.php`

**Query Building** (`src/Dibi/Fluent.php`):
- Fluent interface for building dynamic SQL queries
- Methods: `select()`, `from()`, `where()`, `join()`, `orderBy()`, `limit()`, etc.
- Supports SELECT, INSERT, UPDATE, DELETE operations
- Cloning support for query variants

**Parameter Translation** (`src/Dibi/Translator.php`):
- Most complex component - converts parameter modifiers to driver-specific escaping
- Handles modifiers: `%s`, `%i`, `%n`, `%d`, `%dt`, `%and`, `%or`, `%a`, `%v`, etc.
- Smart array expansion for IN clauses
- Conditional SQL blocks with `%if...%end`
- Expression support via `Literal` and `Expression`

**Result Handling** (`src/Dibi/Result.php`, `src/Dibi/Row.php`):
- Lazy loading of rows from drivers
- Multiple fetch methods: `fetch()`, `fetchAll()`, `fetchAssoc()`, `fetchPairs()`, `fetchSingle()`
- Automatic type detection and conversion
- Iterator implementation for memory-efficient processing

**Database Reflection** (`src/Dibi/Reflection/`):
- Schema introspection API for tables, columns, indexes, foreign keys
- Works through `Reflector` interface implemented by each driver
- Key classes: `Database`, `Table`, `Column`, `Index`, `ForeignKey`

**Framework Integration** (`src/Dibi/Bridges/`):
- `Nette/DibiExtension3.php` - Nette 3.x DI container integration
- `Tracy/Panel.php` - Tracy debugger panel for query visualization

### Key Interfaces

Defined in `src/Dibi/interfaces.php`:
- `Driver` - Database-specific operations (query, escape, reflection)
- `ResultDriver` - Result set handling
- `Reflector` - Schema introspection
- `IConnection` - Connection contract
- `IDataSource` - Generic data source abstraction

### Static Facade

`src/Dibi/dibi.php` provides a static facade for global access to the default connection instance (backward compatibility layer).

## Code Standards

### PHP Coding Style

- **Strict types required**: `declare(strict_types=1)` in all files
- **Tabs for indentation** (not spaces)
- **Single quotes** for strings (except HTML)
- **Two empty lines between methods**
- **PSR-12 compliant** with Nette modifications
- **Type declarations**: All properties, parameters, and return types must be typed
- **Return type and opening brace on separate lines**:

```php
public function example(
	string $param,
	array $options,
): void
{
	// method body
}
```

### Documentation (PHPDoc)

- Never duplicate signature information without adding value
- Document array contents: `@return string[]`
- Use two spaces after type/param: `@return string  description`
- Only document when explaining additional context, limitations, or unusual usage
- For exceptions, describe the problem in active voice without "Exception that is thrown when"

### Naming Conventions

- Avoid abbreviations unless full name is too long
- UPPERCASE for two-letter abbreviations, PascalCase/camelCase for longer ones
- PascalCase for classes, camelCase for methods/properties
- Never use prefixes like `Abstract`, `Interface`, or `I`

## Testing Patterns

### Test File Structure

Tests use **Nette Tester** with `.phpt` extension:

```php
<?php

declare(strict_types=1);

use Tester\Assert;
use Dibi\Connection;

require __DIR__ . '/bootstrap.php';

$conn = new Connection($config);
$conn->loadFile(__DIR__ . "/data/$config[system].sql");

// Test description as first parameter
Assert::same(3, $conn->dataSource('SELECT * FROM products')->count());

Assert::exception(
	fn() => $conn->query('INVALID SQL'),
	Dibi\Exception::class,
	'SQL error message',
);
```

### Key Testing Principles

- Each test file covers a specific class or feature
- Use `Assert::same()` for strict comparisons
- Use `Assert::match()` for pattern matching with SQL
- Use `Assert::exception()` for exception testing
- Tests are located in `tests/dibi/`
- Test data SQL files in `tests/dibi/data/`

## Driver-Specific Development

When modifying or adding drivers:

1. **Implement core interfaces**: `Driver`, `ResultDriver`, `Reflector`
2. **Handle type escaping** for: text, binary, identifier, boolean, date, datetime
3. **SQL injection methods**: How to add LIMIT/OFFSET to queries
4. **Result handling**: Create driver-specific `*Result` class
5. **Schema reflection**: Create driver-specific `*Reflector` class

## Common Development Patterns

### Adding New Modifiers

Modifiers are handled in `src/Dibi/Translator.php`. The translation logic maps modifiers to driver-specific escaping:

- Check `formatValue()` method for value formatting
- Update `modifier()` method for new modifier types
- Test with multiple drivers

### Working with Results

Result processing happens in two stages:
1. **Driver-level**: `ResultDriver` implementation returns raw data
2. **Abstraction-level**: `Result` class processes and normalizes data

### Exception Hierarchy

All exceptions inherit from `Dibi\Exception`. Specific exceptions in `src/Dibi/exceptions.php`:
- `ConstraintViolationException`
- `ForeignKeyConstraintViolationException`
- `NotNullConstraintViolationException`
- `UniqueConstraintViolationException`

## Supported Databases

The library supports these database systems (drivers in `src/Dibi/Drivers/`):

- **MySQL/MariaDB** (via MySqli or PDO)
- **PostgreSQL** (native or PDO)
- **SQLite 3** (native or PDO)
- **MS SQL Server** (2012+, PDO or sqlsrv)
- **Oracle** (OCI8 or PDO)
- **Firebird/Interbase**
- **ODBC**
- **DummyDriver** for testing

## Query Language Features

### Parameter Modifiers

Basic value modifiers:
- `%s` - string (NULL if value is null)
- `%sN` - string, but '' translates as NULL
- `%bin` - binary data
- `%b` - boolean
- `%i` - integer
- `%iN` - integer, but 0 translates as NULL
- `%f` - float
- `%d` - date (accepts DateTime, string or UNIX timestamp)
- `%dt` - datetime (accepts DateTime, string or UNIX timestamp)
- `%n` - identifier (table or column name, handles dots for qualified names)
- `%N` - identifier, treats period as ordinary character (for aliases, database names)
- `%SQL` - direct SQL insertion (alternative: `Dibi\Literal`)
- `%ex` - expands array of SQL expressions
- `%lmt` - adds LIMIT clause
- `%ofs` - adds OFFSET clause

LIKE modifiers for pattern matching:
- `%like~` - expression starts with string
- `%~like` - expression ends with string
- `%~like~` - expression contains string
- `%like` - expression matches string

### Array Modifiers

When passing arrays as parameters:
- `%and` - `key1 = value1 AND key2 = value2 AND ...`
- `%or` - `key1 = value1 OR key2 = value2 OR ...`
- `%a` (assoc) - `key1 = value1, key2 = value2, ...` (for UPDATE SET)
- `%l` or `%in` (list) - `(val1, val2, ...)` (for IN clauses)
- `%v` (values) - `(key1, key2, ...) VALUES (value1, value2, ...)` (for INSERT)
- `%m` (multi) - multiple value rows for batch INSERT
- `%by` (ordering) - `key1 ASC, key2 DESC ...` (for ORDER BY)
- `%n` (names) - `key1, key2 AS alias, ...` (for SELECT columns)

Example usage:
```php
// UPDATE with %a
$database->query('UPDATE users SET %a', ['name' => 'Jim', 'year' => 1978]);

// WHERE with %and
$database->query('SELECT * FROM users WHERE %and', ['name' => 'Jim', 'active' => true]);

// INSERT with %v
$database->query('INSERT INTO users %v', ['name' => 'Jim', 'year' => 1978]);

// Multiple INSERT with %m
$database->query('INSERT INTO users %m', [
	['name' => 'Jim', 'year' => 1978],
	['name' => 'Jack', 'year' => 1987],
]);
```

### Advanced Query Features

**Modifiers in array keys** (for UPDATE):
```php
$database->query('UPDATE table SET', [
	'date%SQL' => 'NOW()',
	'title' => 'normal value',
]);
// UPDATE table SET `date` = NOW(), `title` = 'normal value'
```

**Literal SQL** (bypass escaping):
```php
$database->literal('NOW()'); // returns Dibi\Literal object
```

**Expressions** (with parameters):
```php
$database::expression('SHA1(?)', 'secret'); // returns Dibi\Expression object
```

**Conditional SQL** (`%if`, `%else`, `%end`):
```php
$database->query('
	SELECT *
	FROM table
	%if', isset($user), 'WHERE user=%s', $user, '%end
	ORDER BY name
');
```

**Nested conditions in WHERE** (arrays without keys):
```php
$database->query('SELECT * FROM table WHERE %and', [
	'number > 10',
	'number < 100',
	['%or', ['left' => 1, 'top' => 2]],
]);
// WHERE (number > 10) AND (number < 100) AND (`left` = 1 OR `top` = 2)
```

**Table/column substitutions** (prefixes):
```php
$database->substitute('blog', 'wp_');
$database->query("UPDATE [:blog:items] SET [text]='Hello'");
// UPDATE `wp_items` SET `text`='Hello'
```

### Result Fetching Methods

**Advanced fetchAssoc()** - Reshapes flat JOIN results into nested arrays:

```php
// Simple associative array by key
$result->fetchAssoc('id');  // returns [$id => $row, ...]

// Two-level nesting
$result->fetchAssoc('customer_id|order_id');
// returns [$customerId => [$orderId => $row, ...], ...]

// With intermediate row object
$result->fetchAssoc('customer_id->order_id');
// returns [$customerId => $row with ->order_id[$orderId], ...]

// With sequential array for duplicate keys
$result->fetchAssoc('name[]order_id');
// returns [$name => [[$orderId => $row], [$orderId2 => $row2]], ...]
```

The descriptor syntax:
- `|` - creates new array level (associative by key)
- `->` - inserts row object as intermediate element
- `[]` - creates sequential array for duplicate keys

### Transaction Methods

Four methods for transaction handling:
```php
$database->beginTransaction();
$database->commit();
$database->rollback();

// Or using callback (auto-commits on success, auto-rolls back on exception)
$database->transaction(function () use ($database) {
	$database->query('...');
	$database->query('...');
});
```

### Debugging and Testing

**Query inspection** without execution:
```php
$database->test('SELECT * FROM users WHERE id = ?', $id);
// Echoes the SQL that would be executed
```

**Result dumping**:
```php
$result->dump(); // Outputs result as HTML table
```

**Query statistics** (available via static facade):
```php
dibi::$sql;          // Latest SQL query executed
dibi::$elapsedTime;  // Duration in seconds
dibi::$numOfQueries; // Total number of queries
dibi::$totalTime;    // Total execution time
```

**Logging configuration**:
```php
$database = new Dibi\Connection([
	'driver' => 'mysqli',
	// ... connection params
	'profiler' => [
		'file' => 'queries.log',
	],
]);
```

### Type Handling

**Manual type specification**:
```php
$result->setType('id', Dibi\Type::INTEGER);
$result->setType('price', Dibi\Type::FLOAT);
$row = $result->fetch();
// Now $row->id is guaranteed to be int
```

**DateTime support**:
```php
$database->query('INSERT INTO users', [
	'created' => new DateTime,
	'expires' => new DateTime('+1 year'),
]);
```

### Identifier Quoting

Dibi automatically converts bracket/backtick notation to database-specific format:
```php
$database->query("UPDATE `table` SET [status]='value'");
// MySQL:  UPDATE `table` SET `status`='value'
// ODBC:   UPDATE [table] SET [status]='value'
```

## Important Notes

- **Zero production dependencies** - Core library is completely standalone
- **Lazy connections** - Database connections are established only when first query is executed
- **Event system** - Use `Connection::$onEvent` for query profiling and logging
- **Multi-database testing** - Tests run against MySQL, PostgreSQL, and SQLite configurations
- **PHPStan Level 5** - All code must pass static analysis at level 5
- **SQL injection protection** - NEVER concatenate user input into SQL, always use placeholders or modifiers
