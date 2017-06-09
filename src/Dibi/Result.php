<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


/**
 * dibi result set.
 *
 * <code>
 * $result = dibi::query('SELECT * FROM [table]');
 *
 * $row   = $result->fetch();
 * $value = $result->fetchSingle();
 * $table = $result->fetchAll();
 * $pairs = $result->fetchPairs();
 * $assoc = $result->fetchAssoc('col1');
 * $assoc = $result->fetchAssoc('col1[]col2->col3');
 *
 * unset($result);
 * </code>
 *
 * @property-read int $rowCount
 */
class Result implements IDataSource
{
	use Strict;

	/** @var array  ResultDriver */
	private $driver;

	/** @var array  Translate table */
	private $types = [];

	/** @var Reflection\Result */
	private $meta;

	/** @var bool  Already fetched? Used for allowance for first seek(0) */
	private $fetched = FALSE;

	/** @var string  returned object class */
	private $rowClass = Row::class;

	/** @var callable  returned object factory*/
	private $rowFactory;

	/** @var array  format */
	private $formats = [];


	public function __construct(ResultDriver $driver)
	{
		$this->driver = $driver;
		$this->detectTypes();
	}


	/**
	 * Frees the resources allocated for this result set.
	 */
	final public function free(): void
	{
		if ($this->driver !== NULL) {
			$this->driver->free();
			$this->driver = $this->meta = NULL;
		}
	}


	/**
	 * Safe access to property $driver.
	 * @throws \RuntimeException
	 */
	final public function getResultDriver(): ResultDriver
	{
		if ($this->driver === NULL) {
			throw new \RuntimeException('Result-set was released from memory.');
		}

		return $this->driver;
	}


	/********************* rows ****************d*g**/


	/**
	 * Moves cursor position without fetching row.
	 * @throws Exception
	 */
	final public function seek(int $row): bool
	{
		return ($row !== 0 || $this->fetched) ? (bool) $this->getResultDriver()->seek($row) : TRUE;
	}


	/**
	 * Required by the Countable interface.
	 */
	final public function count(): int
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Returns the number of rows in a result set.
	 */
	final public function getRowCount(): int
	{
		return $this->getResultDriver()->getRowCount();
	}


	/**
	 * Required by the IteratorAggregate interface.
	 */
	final public function getIterator(): ResultIterator
	{
		return new ResultIterator($this);
	}


	/********************* fetching rows ****************d*g**/


	/**
	 * Set fetched object class. This class should extend the Row class.
	 */
	public function setRowClass(string $class): self
	{
		$this->rowClass = $class;
		return $this;
	}


	/**
	 * Returns fetched object class name.
	 */
	public function getRowClass(): string
	{
		return $this->rowClass;
	}


	/**
	 * Set a factory to create fetched object instances. These should extend the Row class.
	 */
	public function setRowFactory(callable $callback): self
	{
		$this->rowFactory = $callback;
		return $this;
	}


	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 */
	final public function fetch(): ?Row
	{
		$row = $this->getResultDriver()->fetch(TRUE);
		if ($row === NULL) {
			return NULL;
		}
		$this->fetched = TRUE;
		$this->normalize($row);
		if ($this->rowFactory) {
			return ($this->rowFactory)($row);
		} elseif ($this->rowClass) {
			return new $this->rowClass($row);
		}
		return $row;
	}


	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed value on success, NULL if no next record
	 */
	final public function fetchSingle()
	{
		$row = $this->getResultDriver()->fetch(TRUE);
		if ($row === NULL) {
			return NULL;
		}
		$this->fetched = TRUE;
		$this->normalize($row);
		return reset($row);
	}


	/**
	 * Fetches all records from table.
	 * @return Row[]
	 */
	final public function fetchAll(int $offset = NULL, int $limit = NULL): array
	{
		$limit = $limit === NULL ? -1 : (int) $limit;
		$this->seek((int) $offset);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];
		do {
			if ($limit === 0) {
				break;
			}
			$limit--;
			$data[] = $row;
		} while ($row = $this->fetch());

		return $data;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * Examples:
	 * - associative descriptor: col1[]col2->col3
	 *   builds a tree:          $tree[$val1][$index][$val2]->col3[$val3] = {record}
	 * - associative descriptor: col1|col2->col3=col4
	 *   builds a tree:          $tree[$val1][$val2]->col3[$val3] = val4
	 * @param  string  associative descriptor
	 * @throws \InvalidArgumentException
	 */
	final public function fetchAssoc(string $assoc): array
	{
		if (strpos($assoc, ',') !== FALSE) {
			return $this->oldFetchAssoc($assoc);
		}

		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = NULL;
		$assoc = preg_split('#(\[\]|->|=|\|)#', $assoc, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '[]' && $as !== '=' && $as !== '->' && $as !== '|' && !property_exists($row, $as)) {
				throw new \InvalidArgumentException("Unknown column '$as' in associative descriptor.");
			}
		}

		if ($as === '->') { // must not be last
			array_pop($assoc);
		}

		if (empty($assoc)) {
			$assoc[] = '[]';
		}

		// make associative tree
		do {
			$x = &$data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '[]') { // indexed-array node
					$x = &$x[];

				} elseif ($as === '=') { // "value" node
					$x = $row->{$assoc[$i + 1]};
					continue 2;

				} elseif ($as === '->') { // "object" node
					if ($x === NULL) {
						$x = clone $row;
						$x = &$x->{$assoc[$i + 1]};
						$x = NULL; // prepare child node
					} else {
						$x = &$x->{$assoc[$i + 1]};
					}

				} elseif ($as !== '|') { // associative-array node
					$x = &$x[$row->$as];
				}
			}

			if ($x === NULL) { // build leaf
				$x = $row;
			}

		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * @deprecated
	 */
	private function oldFetchAssoc($assoc)
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = NULL;
		$assoc = explode(',', $assoc);

		// strip leading = and @
		$leaf = '@';  // gap
		$last = count($assoc) - 1;
		while ($assoc[$last] === '=' || $assoc[$last] === '@') {
			$leaf = $assoc[$last];
			unset($assoc[$last]);
			$last--;

			if ($last < 0) {
				$assoc[] = '#';
				break;
			}
		}

		do {
			$x = &$data;

			foreach ($assoc as $i => $as) {
				if ($as === '#') { // indexed-array node
					$x = &$x[];

				} elseif ($as === '=') { // "record" node
					if ($x === NULL) {
						$x = $row->toArray();
						$x = &$x[ $assoc[$i + 1] ];
						$x = NULL; // prepare child node
					} else {
						$x = &$x[ $assoc[$i + 1] ];
					}

				} elseif ($as === '@') { // "object" node
					if ($x === NULL) {
						$x = clone $row;
						$x = &$x->{$assoc[$i + 1]};
						$x = NULL; // prepare child node
					} else {
						$x = &$x->{$assoc[$i + 1]};
					}

				} else { // associative-array node
					$x = &$x[$row->$as];
				}
			}

			if ($x === NULL) { // build leaf
				if ($leaf === '=') {
					$x = $row->toArray();
				} else {
					$x = $row;
				}
			}

		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @param  string  associative key
	 * @throws \InvalidArgumentException
	 */
	final public function fetchPairs(string $key = NULL, string $value = NULL): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];

		if ($value === NULL) {
			if ($key !== NULL) {
				throw new \InvalidArgumentException('Either none or both columns must be specified.');
			}

			// autodetect
			$tmp = array_keys($row->toArray());
			$key = $tmp[0];
			if (count($row) < 2) { // indexed-array
				do {
					$data[] = $row[$key];
				} while ($row = $this->fetch());
				return $data;
			}

			$value = $tmp[1];

		} else {
			if (!property_exists($row, $value)) {
				throw new \InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch());
				return $data;
			}

			if (!property_exists($row, $key)) {
				throw new \InvalidArgumentException("Unknown key column '$key'.");
			}
		}

		do {
			$data[ (string) $row[$key] ] = $row[$value];
		} while ($row = $this->fetch());

		return $data;
	}


	/********************* column types ****************d*g**/


	/**
	 * Autodetect column types.
	 */
	private function detectTypes(): void
	{
		$cache = Helpers::getTypeCache();
		try {
			foreach ($this->getResultDriver()->getResultColumns() as $col) {
				$this->types[$col['name']] = $col['type'] ?? $cache->{$col['nativetype']};
			}
		} catch (NotSupportedException $e) {
		}
	}


	/**
	 * Converts values to specified type and format.
	 */
	private function normalize(array &$row): void
	{
		foreach ($this->types as $key => $type) {
			if (!isset($row[$key])) { // NULL
				continue;
			}
			$value = $row[$key];
			if ($type === Type::TEXT) {
				$row[$key] = (string) $value;

			} elseif ($type === Type::INTEGER) {
				$row[$key] = is_float($tmp = $value * 1)
					? (is_string($value) ? $value : (int) $value)
					: $tmp;

			} elseif ($type === Type::FLOAT) {
				$value = ltrim((string) $value, '0');
				$p = strpos($value, '.');
				if ($p !== FALSE) {
					$value = rtrim(rtrim($value, '0'), '.');
				}
				if ($value === '' || $value[0] === '.') {
					$value = '0' . $value;
				}
				$row[$key] = $value === str_replace(',', '.', (string) ($float = (float) $value))
					? $float
					: $value;

			} elseif ($type === Type::BOOL) {
				$row[$key] = ((bool) $value) && $value !== 'f' && $value !== 'F';

			} elseif ($type === Type::DATETIME || $type === Type::DATE || $type === Type::TIME) {
				if ($value && substr((string) $value, 0, 3) !== '000') { // '', NULL, FALSE, '0000-00-00', ...
					$value = new DateTime($value);
					$row[$key] = empty($this->formats[$type]) ? $value : $value->format($this->formats[$type]);
				} else {
					$row[$key] = NULL;
				}

			} elseif ($type === Type::TIME_INTERVAL) {
				preg_match('#^(-?)(\d+)\D(\d+)\D(\d+)\z#', $value, $m);
				$row[$key] = new \DateInterval("PT$m[2]H$m[3]M$m[4]S");
				$row[$key]->invert = (int) (bool) $m[1];

			} elseif ($type === Type::BINARY) {
				$row[$key] = $this->getResultDriver()->unescapeBinary($value);
			}
		}
	}


	/**
	 * Define column type.
	 * @param  string  column
	 * @param  string  type (use constant Type::*)
	 */
	final public function setType(string $col, string $type): self
	{
		$this->types[$col] = $type;
		return $this;
	}


	/**
	 * Returns column type.
	 */
	final public function getType($col): string
	{
		return $this->types[$col] ?? NULL;
	}


	/**
	 * Sets date format.
	 * @param  string
	 */
	final public function setFormat(string $type, ?string $format): self
	{
		$this->formats[$type] = $format;
		return $this;
	}


	/**
	 * Returns data format.
	 */
	final public function getFormat($type): ?string
	{
		return $this->formats[$type] ?? NULL;
	}


	/********************* meta info ****************d*g**/


	/**
	 * Returns a meta information about the current result set.
	 */
	public function getInfo(): Reflection\Result
	{
		if ($this->meta === NULL) {
			$this->meta = new Reflection\Result($this->getResultDriver());
		}
		return $this->meta;
	}


	/**
	 * @return Reflection\Column[]
	 */
	final public function getColumns(): array
	{
		return $this->getInfo()->getColumns();
	}


	/********************* misc tools ****************d*g**/


	/**
	 * Displays complete result set as HTML or text table for debug purposes.
	 */
	final public function dump(): void
	{
		echo Helpers::dump($this);
	}

}
