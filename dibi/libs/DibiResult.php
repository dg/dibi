<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 *
 * Copyright (c) 2005, 2010 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license", and/or
 * GPL license. For more information please see http://dibiphp.com
 * @package    dibi
 */



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
 * $assoc = $result->fetchAssoc('id');
 * $assoc = $result->fetchAssoc('active,#,id');
 *
 * unset($result);
 * </code>
 *
 * @author     David Grudl
 *
 * @property-read mixed $resource
 * @property-read IDibiResultDriver $driver
 * @property-read int $rowCount
 * @property-read DibiResultIterator $iterator
 * @property string $rowClass
 * @property-read DibiResultInfo $info
 */
class DibiResult extends DibiObject implements IDataSource
{
	/** @var array  IDibiResultDriver */
	private $driver;

	/** @var array  Translate table */
	private $types;

	/** @var DibiResultInfo */
	private $meta;

	/** @var bool  Already fetched? Used for allowance for first seek(0) */
	private $fetched = FALSE;

	/** @var string  returned object class */
	private $rowClass = 'DibiRow';

	/** @var string  date-time format */
	private $dateFormat = '';



	/**
	 * @param  IDibiResultDriver
	 * @param  array
	 */
	public function __construct($driver, $config)
	{
		$this->driver = $driver;

		if (!empty($config['detectTypes'])) {
			$this->detectTypes();
		}

		if (!empty($config['formatDateTime'])) {
			$this->dateFormat = is_string($config['formatDateTime']) ? $config['formatDateTime'] : '';
		}
	}



	/**
	 * Returns the result set resource.
	 * @return mixed
	 */
	final public function getResource()
	{
		return $this->getDriver()->getResultResource();
	}



	/**
	 * Frees the resources allocated for this result set.
	 * @return void
	 */
	final public function free()
	{
		if ($this->driver !== NULL) {
			$this->driver->free();
			$this->driver = $this->meta = NULL;
		}
	}



	/**
	 * Safe access to property $driver.
	 * @return IDibiResultDriver
	 * @throws InvalidStateException
	 */
	private function getDriver()
	{
		if ($this->driver === NULL) {
			throw new InvalidStateException('Result-set was released from memory.');
		}

		return $this->driver;
	}



	/********************* rows ****************d*g**/



	/**
	 * Moves cursor position without fetching row.
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	final public function seek($row)
	{
		return ($row !== 0 || $this->fetched) ? (bool) $this->getDriver()->seek($row) : TRUE;
	}



	/**
	 * Required by the Countable interface.
	 * @return int
	 */
	final public function count()
	{
		return $this->getDriver()->getRowCount();
	}



	/**
	 * Returns the number of rows in a result set.
	 * @return int
	 */
	final public function getRowCount()
	{
		return $this->getDriver()->getRowCount();
	}



	/**
	 * Returns the number of rows in a result set. Alias for getRowCount().
	 * @deprecated
	 */
	final public function rowCount()
	{
		return $this->getDriver()->getRowCount();
	}



	/**
	 * Required by the IteratorAggregate interface.
	 * @return DibiResultIterator
	 */
	final public function getIterator()
	{
		if (func_num_args()) {
			trigger_error(__METHOD__ . ' arguments $offset & $limit have been dropped; use SQL clauses instead.', E_USER_WARNING);
		}
		return new DibiResultIterator($this);
	}



	/********************* fetching rows ****************d*g**/



	/**
	 * Set fetched object class. This class should extend the DibiRow class.
	 * @param  string
	 * @return DibiResult  provides a fluent interface
	 */
	public function setRowClass($class)
	{
		$this->rowClass = $class;
		return $this;
	}



	/**
	 * Returns fetched object class name.
	 * @return string
	 */
	public function getRowClass()
	{
		return $this->rowClass;
	}



	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 * @return DibiRow|FALSE  array on success, FALSE if no next record
	 */
	final public function fetch()
	{
		$row = $this->getDriver()->fetch(TRUE);
		if (!is_array($row)) return FALSE;

		$this->fetched = TRUE;

		// types-converting?
		if ($this->types !== NULL) {
			foreach ($this->types as $col => $type) {
				if (isset($row[$col])) {
					$row[$col] = $this->convert($row[$col], $type);
				}
			}
		}

		return new $this->rowClass($row);
	}



	/**
	 * Like fetch(), but returns only first field.
	 * @return mixed  value on success, FALSE if no next record
	 */
	final public function fetchSingle()
	{
		$row = $this->getDriver()->fetch(TRUE);
		if (!is_array($row)) return FALSE;
		$this->fetched = TRUE;
		$value = reset($row);

		// types-converting?
		$key = key($row);
		if (isset($this->types[$key])) {
			return $this->convert($value, $this->types[$key]);
		}

		return $value;
	}



	/**
	 * Fetches all records from table.
	 * @param  int  offset
	 * @param  int  limit
	 * @return array of DibiRow
	 */
	final public function fetchAll($offset = NULL, $limit = NULL)
	{
		$limit = $limit === NULL ? -1 : (int) $limit;
		$this->seek((int) $offset);
		$row = $this->fetch();
		if (!$row) return array();  // empty result set

		$data = array();
		do {
			if ($limit === 0) break;
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
	 * @return DibiRow
	 * @throws InvalidArgumentException
	 */
	final public function fetchAssoc($assoc)
	{
		if (strpos($assoc, ',') !== FALSE) {
			return $this->oldFetchAssoc($assoc);
		}

		$this->seek(0);
		$row = $this->fetch();
		if (!$row) return array();  // empty result set

		$data = NULL;
		$assoc = preg_split('#(\[\]|->|=|\|)#', $assoc, NULL, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '[]' && $as !== '=' && $as !== '->' && $as !== '|' && !property_exists($row, $as)) {
				throw new InvalidArgumentException("Unknown column '$as' in associative descriptor.");
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
			$x = & $data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '[]') { // indexed-array node
					$x = & $x[];

				} elseif ($as === '=') { // "value" node
					$x = $row->{$assoc[$i+1]};
					continue 2;

				} elseif ($as === '->') { // "object" node
					if ($x === NULL) {
						$x = clone $row;
						$x = & $x->{$assoc[$i+1]};
						$x = NULL; // prepare child node
					} else {
						$x = & $x->{$assoc[$i+1]};
					}

				} elseif ($as !== '|') { // associative-array node
					$x = & $x[$row->$as];
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
		if (!$row) return array();  // empty result set

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
			$x = & $data;

			foreach ($assoc as $i => $as) {
				if ($as === '#') { // indexed-array node
					$x = & $x[];

				} elseif ($as === '=') { // "record" node
					if ($x === NULL) {
						$x = $row->toArray();
						$x = & $x[ $assoc[$i+1] ];
						$x = NULL; // prepare child node
					} else {
						$x = & $x[ $assoc[$i+1] ];
					}

				} elseif ($as === '@') { // "object" node
					if ($x === NULL) {
						$x = clone $row;
						$x = & $x->{$assoc[$i+1]};
						$x = NULL; // prepare child node
					} else {
						$x = & $x->{$assoc[$i+1]};
					}


				} else { // associative-array node
					$x = & $x[$row->$as];
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
	 * @param  string  value
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final public function fetchPairs($key = NULL, $value = NULL)
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) return array();  // empty result set

		$data = array();

		if ($value === NULL) {
			if ($key !== NULL) {
				throw new InvalidArgumentException("Either none or both columns must be specified.");
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
				throw new InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch());
				return $data;
			}

			if (!property_exists($row, $key)) {
				throw new InvalidArgumentException("Unknown key column '$key'.");
			}
		}

		do {
			$data[ $row[$key] ] = $row[$value];
		} while ($row = $this->fetch());

		return $data;
	}



	/********************* meta info ****************d*g**/



	/**
	 * Define column type.
	 * @param  string  column
	 * @param  string  type (use constant Dibi::*)
	 * @return DibiResult  provides a fluent interface
	 */
	final public function setType($col, $type)
	{
		$this->types[$col] = $type;
		return $this;
	}



	/**
	 * Autodetect column types.
	 * @return void
	 */
	final public function detectTypes()
	{
		foreach ($this->getInfo()->getColumns() as $col) {
			$this->types[$col->getName()] = $col->getType();
		}
	}



	/**
	 * Define multiple columns types.
	 * @param  array
	 * @return DibiResult  provides a fluent interface
	 * @internal
	 */
	final public function setTypes(array $types)
	{
		$this->types = $types;
		return $this;
	}



	/**
	 * Returns column type.
	 * @return string
	 */
	final public function getType($col)
	{
		return isset($this->types[$col]) ? $this->types[$col] : NULL;
	}



	/**
	 * Converts value to specified type and format.
	 * @param  mixed  value
	 * @param  int    type
	 * @return mixed
	 */
	protected function convert($value, $type)
	{
		if ($value === NULL || $value === FALSE) {
			return NULL;
		}

		switch ($type) {
		case dibi::TEXT:
			return (string) $value;

		case dibi::BINARY:
			return $this->getDriver()->unescape($value, $type);

		case dibi::INTEGER:
			return (int) $value;

		case dibi::FLOAT:
			return (float) $value;

		case dibi::DATE:
		case dibi::DATETIME:
			if ((int) $value === 0) { // '', NULL, FALSE, '0000-00-00', ...
				return NULL;

			} elseif ($this->dateFormat === '') { // return DateTime object (default)
				return new DibiDateTime(is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value);

			} elseif ($this->dateFormat === 'U') { // return timestamp
				return is_numeric($value) ? (int) $value : strtotime($value);

			} elseif (is_numeric($value)) { // formatted date
				return date($this->dateFormat, $value);

			} else {
				$value = new DibiDateTime($value);
				return $value->format($this->dateFormat);
			}

		case dibi::BOOL:
			return ((bool) $value) && $value !== 'f' && $value !== 'F';

		default:
			return $value;
		}
	}



	/**
	 * Returns a meta information about the current result set.
	 * @return DibiResultInfo
	 */
	public function getInfo()
	{
		if ($this->meta === NULL) {
			$this->meta = new DibiResultInfo($this->getDriver());
		}
		return $this->meta;
	}



	/**
	 * @deprecated
	 */
	final public function getColumns()
	{
		return $this->getInfo()->getColumns();
	}



	/**
	 * @deprecated
	 */
	public function getColumnNames($fullNames = FALSE)
	{
		return $this->getInfo()->getColumnNames($fullNames);
	}



	/********************* misc tools ****************d*g**/



	/**
	 * Displays complete result set as HTML table for debug purposes.
	 * @return void
	 */
	final public function dump()
	{
		$i = 0;
		$this->seek(0);
		while ($row = $this->fetch()) {
			if ($i === 0) {
				echo "\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";

				foreach ($row as $col => $foo) {
					echo "\t\t<th>" . htmlSpecialChars($col) . "</th>\n";
				}

				echo "\t</tr>\n</thead>\n<tbody>\n";
			}

			echo "\t<tr>\n\t\t<th>", $i, "</th>\n";
			foreach ($row as $col) {
				//if (is_object($col)) $col = $col->__toString();
				echo "\t\t<td>", htmlSpecialChars($col), "</td>\n";
			}
			echo "\t</tr>\n";
			$i++;
		}

		if ($i === 0) {
			echo '<p><em>empty result set</em></p>';
		} else {
			echo "</tbody>\n</table>\n";
		}
	}

}
