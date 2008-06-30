<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * dibi result-set abstract class.
 *
 * <code>
 * $result = dibi::query('SELECT * FROM [table]');
 *
 * $row   = $result->fetch();
 * $obj   = $result->fetch(TRUE);
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
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiResult extends /*Nette::*/Object implements IDataSource
{
	/**
	 * IDibiDriver.
	 * @var array
	 */
	private $driver;

	/**
	 * Translate table.
	 * @var array
	 */
	private $xlat;

	/**
	 * Cache for $driver->getColumnsMeta().
	 * @var array
	 */
	private $metaCache;

	/**
	 * Already fetched? Used for allowance for first seek(0).
	 * @var bool
	 */
	private $fetched = FALSE;

	/**
	 * Qualifiy each column name with the table name?
	 * @var array|FALSE
	 */
	private $withTables = FALSE;

	/**
	 * Fetch as objects or arrays?
	 * @var mixed  TRUE | FALSE | class name
	 */
	private $objects = FALSE;



	/**
	 * @param  IDibiDriver
	 * @param  array
	 */
	public function __construct($driver, $config)
	{
		$this->driver = $driver;

		if (!empty($config['result:withtables'])) {
			$this->setWithTables(TRUE);
		}

		if (isset($config['result:objects'])) {
			$this->setObjects($config['result:objects']);
		}
	}



	/**
	 * Automatically frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		@$this->free(); // intentionally @
	}



	/**
	 * Returns the result set resource.
	 *
	 * @return mixed
	 */
	final public function getResource()
	{
		return $this->getDriver()->getResultResource();
	}



	/**
	 * Moves cursor position without fetching row.
	 *
	 * @param  int      the 0-based cursor pos to seek to
	 * @return boolean  TRUE on success, FALSE if unable to seek to specified record
	 * @throws DibiException
	 */
	final public function seek($row)
	{
		return ($row !== 0 || $this->fetched) ? (bool) $this->getDriver()->seek($row) : TRUE;
	}



	/**
	 * Returns the number of rows in a result set.
	 *
	 * @return int
	 */
	final public function rowCount()
	{
		return $this->getDriver()->rowCount();
	}



	/**
	 * Frees the resources allocated for this result set.
	 *
	 * @return void
	 */
	final public function free()
	{
		if ($this->driver !== NULL) {
			$this->driver->free();
			$this->driver = NULL;
		}
	}



	/**
	 * Qualifiy each column name with the table name?
	 *
	 * @param  bool
	 * @return void
	 * @throws DibiException
	 */
	final public function setWithTables($val)
	{
		if ($val) {
			if ($this->metaCache === NULL) {
				$this->metaCache = $this->getDriver()->getColumnsMeta();
			}

			$cols = array();
			foreach ($this->metaCache as $col) {
				// intentional ==
				$name = $col['table'] == '' ? $col['name'] : ($col['table'] . '.' . $col['name']);
				if (isset($cols[$name])) {
					$fix = 1;
					while (isset($cols[$name . '#' . $fix])) $fix++;
					$name .= '#' . $fix;
				}
				$cols[$name] = TRUE;
			}
			$this->withTables = array_keys($cols);

		} else {
			$this->withTables = FALSE;
		}
	}



	/**
	 * Qualifiy each key with the table name?
	 *
	 * @return bool
	 */
	final public function getWithTables()
	{
		return (bool) $this->withTables;
	}



	/**
	 * Returns rows as arrays or objects?
	 *
	 * @param  mixed  TRUE | FALSE | class name
	 * @return void
	 */
	public function setObjects($type)
	{
		$this->objects = $type;
	}



	/**
	 * Returns rows as arrays or objects?
	 *
	 * @return mixed  TRUE | FALSE | class name
	 */
	public function getObjects()
	{
		return $this->objects;
	}



	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 *
	 * @param  mixed  fetch as object? Overrides $this->setObjects()
	 * @return array|FALSE  array on success, FALSE if no next record
	 */
	final public function fetch($objects = NULL)
	{
		if ($this->withTables === FALSE) {
			$row = $this->getDriver()->fetch(TRUE);
			if (!is_array($row)) return FALSE;

		} else {
			$row = $this->getDriver()->fetch(FALSE);
			if (!is_array($row)) return FALSE;
			$row = array_combine($this->withTables, $row);
		}

		$this->fetched = TRUE;

		// types-converting?
		if ($this->xlat !== NULL) {
			foreach ($this->xlat as $col => $type) {
				if (isset($row[$col])) {
					$row[$col] = $this->convert($row[$col], $type[0], $type[1]);
				}
			}
		}

		if ($objects === NULL) {
			$objects = $this->objects;
		}

		if ($objects) {
			if ($objects === TRUE) {
				$row = (object) $row;
			} else {
				$row = new $objects($row);
			}
		}

		return $row;
	}



	/**
	 * Like fetch(), but returns only first field.
	 *
	 * @return mixed  value on success, FALSE if no next record
	 */
	final function fetchSingle()
	{
		$row = $this->getDriver()->fetch(TRUE);
		if (!is_array($row)) return FALSE;
		$this->fetched = TRUE;
		$value = reset($row);

		// types-converting?
		$key = key($row);
		if (isset($this->xlat[$key])) {
			$type = $this->xlat[$key];
			return $this->convert($value, $type[0], $type[1]);
		}

		return $value;
	}



	/**
	 * Fetches all records from table.
	 *
	 * @param  int  offset
	 * @param  int  limit
	 * @param  bool simplify one-column result set?
	 * @return array
	 */
	final function fetchAll($offset = NULL, $limit = NULL, $simplify = TRUE)
	{
		$limit = $limit === NULL ? -1 : (int) $limit;
		$this->seek((int) $offset);
		$row = $this->fetch();
		if (!$row) return array();  // empty result set

		$data = array();
		if ($simplify && !$this->objects && count($row) === 1) {
			// special case: one-column result set
			$key = key($row);
			do {
				if ($limit === 0) break;
				$limit--;
				$data[] = $row[$key];
			} while ($row = $this->fetch());

		} else {
			do {
				if ($limit === 0) break;
				$limit--;
				$data[] = $row;
			} while ($row = $this->fetch());
		}

		return $data;
	}



	/**
	 * Fetches all records from table and returns associative tree.
	 * Associative descriptor:  assoc1,#,assoc2,=,assoc3,@
	 * builds a tree:           $data[assoc1][index][assoc2]['assoc3']->value = {record}
	 *
	 * @param  string  associative descriptor
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final function fetchAssoc($assoc)
	{
		$this->seek(0);
		$row = $this->fetch(FALSE);
		if (!$row) return array();  // empty result set

		$data = NULL;
		$assoc = explode(',', $assoc);

		// check columns
		foreach ($assoc as $as) {
			if ($as !== '#' && $as !== '=' && $as !== '@' && !array_key_exists($as, $row)) {
				throw new InvalidArgumentException("Unknown column '$as' in associative descriptor.");
			}
		}

		// strip leading = and @
		$assoc[] = '=';  // gap
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

		// make associative tree
		do {
			$x = & $data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '#') { // indexed-array node
					$x = & $x[];

				} elseif ($as === '=') { // "record" node
					if ($x === NULL) {
						$x = $row;
						$x = & $x[ $assoc[$i+1] ];
						$x = NULL; // prepare child node
					} else {
						$x = & $x[ $assoc[$i+1] ];
					}

				} elseif ($as === '@') { // "object" node
					if ($x === NULL) {
						$x = (object) $row;
						$x = & $x->{$assoc[$i+1]};
						$x = NULL; // prepare child node
					} else {
						$x = & $x->{$assoc[$i+1]};
					}


				} else { // associative-array node
					$x = & $x[ $row[ $as ] ];
				}
			}

			if ($x === NULL) { // build leaf
				if ($leaf === '=') $x = $row; else $x = (object) $row;
			}

		} while ($row = $this->fetch(FALSE));

		unset($x);
		return $data;
	}



	/**
	 * Fetches all records from table like $key => $value pairs.
	 *
	 * @param  string  associative key
	 * @param  string  value
	 * @return array
	 * @throws InvalidArgumentException
	 */
	final function fetchPairs($key = NULL, $value = NULL)
	{
		$this->seek(0);
		$row = $this->fetch(FALSE);
		if (!$row) return array();  // empty result set

		$data = array();

		if ($value === NULL) {
			if ($key !== NULL) {
				throw new InvalidArgumentException("Either none or both columns must be specified.");
			}

			if (count($row) < 2) {
				throw new UnexpectedValueException("Result must have at least two columns.");
			}

			// autodetect
			$tmp = array_keys($row);
			$key = $tmp[0];
			$value = $tmp[1];

		} else {
			if (!array_key_exists($value, $row)) {
				throw new InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch(FALSE));
				return $data;
			}

			if (!array_key_exists($key, $row)) {
				throw new InvalidArgumentException("Unknown key column '$key'.");
			}
		}

		do {
			$data[ $row[$key] ] = $row[$value];
		} while ($row = $this->fetch(FALSE));

		return $data;
	}



	/**
	 * Define column type.
	 * @param string  column
	 * @param string  type (use constant Dibi::FIELD_*)
	 * @param string  optional format
	 * @return void
	 */
	final public function setType($col, $type, $format = NULL)
	{
		$this->xlat[$col] = array($type, $format);
	}



	/**
	 * Define multiple columns types (for internal usage).
	 * @param array
	 * @return void
	 */
	final public function setTypes(array $types)
	{
		$this->xlat = $types;
	}



	/**
	 * Returns column type.
	 * @return array  ($type, $format)
	 */
	final public function getType($col)
	{
		return isset($this->xlat[$col]) ? $this->xlat[$col] : NULL;
	}



	/**
	 * Converts value to specified type and format
	 * @return array  ($type, $format)
	 */
	final public function convert($value, $type, $format = NULL)
	{
		if ($value === NULL || $value === FALSE) {
			return $value;
		}

		switch ($type) {
		case dibi::FIELD_TEXT:
			return (string) $value;

		case dibi::FIELD_BINARY:
			return $this->getDriver()->unescape($value, $type);

		case dibi::FIELD_INTEGER:
			return (int) $value;

		case dibi::FIELD_FLOAT:
			return (float) $value;

		case dibi::FIELD_DATE:
		case dibi::FIELD_DATETIME:
			$value = strtotime($value);
			return $format === NULL ? $value : date($format, $value);

		case dibi::FIELD_BOOL:
			return ((bool) $value) && $value !== 'f' && $value !== 'F';

		default:
			return $value;
		}
	}



	/**
	 * Gets an array of meta informations about column.
	 *
	 * @return array
	 */
	final public function getColumnsMeta()
	{
		if ($this->metaCache === NULL) {
			$this->metaCache = $this->getDriver()->getColumnsMeta();
		}

		$cols = array();
		foreach ($this->metaCache as $col) {
			$name = (!$this->withTables || $col['table'] === NULL) ? $col['name'] : ($col['table'] . '.' . $col['name']);
			$cols[$name] = $col;
		}
		return $cols;
	}



	/**
	 * Displays complete result-set as HTML table for debug purposes.
	 *
	 * @return void
	 */
	final public function dump()
	{
		$none = TRUE;
		foreach ($this as $i => $row) {
			if ($none) {
				echo "\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";

				foreach ($row as $col => $foo) {
					echo "\t\t<th>" . htmlSpecialChars($col) . "</th>\n";
				}

				echo "\t</tr>\n</thead>\n<tbody>\n";
				$none = FALSE;
			}

			echo "\t<tr>\n\t\t<th>", $i, "</th>\n";
			foreach ($row as $col) {
				//if (is_object($col)) $col = $col->__toString();
				echo "\t\t<td>", htmlSpecialChars($col), "</td>\n";
			}
			echo "\t</tr>\n";
		}

		if ($none) {
			echo '<p><em>empty result set</em></p>';
		} else {
			echo "</tbody>\n</table>\n";
		}
	}



	/**
	 * Required by the IteratorAggregate interface.
	 * @param  int  offset
	 * @param  int  limit
	 * @return ArrayIterator
	 */
	final public function getIterator($offset = NULL, $limit = NULL)
	{
		return new ArrayIterator($this->fetchAll($offset, $limit, FALSE));
	}



	/**
	 * Required by the Countable interface.
	 * @return int
	 */
	final public function count()
	{
		return $this->rowCount();
	}



	/**
	 * Safe access to property $driver.
	 *
	 * @return IDibiDriver
	 * @throws InvalidStateException
	 */
	private function getDriver()
	{
		if ($this->driver === NULL) {
			throw new InvalidStateException('Resultset was released from memory.');
		}

		return $this->driver;
	}


}
