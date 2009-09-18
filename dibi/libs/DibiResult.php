<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2009 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com
 *
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * dibi result-set.
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
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
class DibiResult extends DibiObject implements IDataSource
{
	/** @var array  IDibiDriver */
	private $driver;

	/** @var array  Translate table */
	private $xlat;

	/** @var DibiResultInfo */
	private $meta;

	/** @var bool  Already fetched? Used for allowance for first seek(0) */
	private $fetched = FALSE;

	/** @var array|FALSE  Qualifiy each column name with the table name? */
	private $withTables = FALSE;

	/** @var string  returned object class */
	private $class = 'DibiRow';



	/**
	 * @param  IDibiDriver
	 * @param  array
	 */
	public function __construct($driver, $config)
	{
		$this->driver = $driver;

		if (!empty($config[dibi::RESULT_WITH_TABLES])) {
			$this->setWithTables(TRUE);
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
	 * @return IDibiDriver
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
	 * @param  int  offset
	 * @param  int  limit
	 * @return DibiResultIterator
	 */
	final public function getIterator($offset = NULL, $limit = NULL)
	{
		return new DibiResultIterator($this, $offset, $limit);
	}



	/********************* fetching rows ****************d*g**/



	/**
	 * Set fetched object class. This class should extend the DibiRow class.
	 * @param  string
	 * @return DibiResult  provides a fluent interface
	 */
	public function setRowClass($class)
	{
		$this->class = $class;
		return $this;
	}



	/**
	 * Returns fetched object class name.
	 * @return string
	 */
	public function getRowClass()
	{
		return $this->class;
	}



	/**
	 * Fetches the row at current position, process optional type conversion.
	 * and moves the internal cursor to the next position
	 * @return DibiRow|FALSE  array on success, FALSE if no next record
	 */
	final public function fetch()
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
					$row[$col] = $this->convert($row[$col], $type['type'], $type['format']);
				}
			}
		}

		return new $this->class($row);
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
		if (isset($this->xlat[$key])) {
			$type = $this->xlat[$key];
			return $this->convert($value, $type['type'], $type['format']);
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
	 * Associative descriptor:  assoc1,#,assoc2,=,assoc3,@
	 * builds a tree:           $data[assoc1][index][assoc2]['assoc3']->value = {record}
	 * @param  string  associative descriptor
	 * @return DibiRow
	 * @throws InvalidArgumentException
	 */
	final public function fetchAssoc($assoc)
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) return array();  // empty result set

		$data = NULL;
		$assoc = explode(',', $assoc);

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '#' && $as !== '=' && $as !== '@' && !isset($row[$as])) {
				throw new InvalidArgumentException("Unknown column '$as' in associative descriptor.");
			}
		}

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

		// make associative tree
		do {
			$arr = (array) $row;
			$x = & $data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '#') { // indexed-array node
					$x = & $x[];

				} elseif ($as === '=') { // "record" node
					if ($x === NULL) {
						$x = $arr;
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
					$x = & $x[ $arr[ $as ] ];
				}
			}

			if ($x === NULL) { // build leaf
				if ($leaf === '=') {
					$x = $arr;
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
			$tmp = array_keys((array) $row);
			$key = $tmp[0];
			if (count($row) < 2) { // indexed-array
				do {
					$data[] = $row[$key];
				} while ($row = $this->fetch());
				return $data;
			}

			$value = $tmp[1];

		} else {
			if (!isset($row[$value])) {
				throw new InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch());
				return $data;
			}

			if (!isset($row[$key])) {
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
	 * Qualifiy each column name with the table name?
	 * @param  bool
	 * @return DibiResult  provides a fluent interface
	 * @throws DibiException
	 */
	final public function setWithTables($val)
	{
		if ($val) {
			$cols = array();
			foreach ($this->getInfo()->getColumns() as $col) {
				$name = $col->getFullname();
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
		return $this;
	}



	/**
	 * Qualifiy each key with the table name?
	 * @return bool
	 */
	final public function getWithTables()
	{
		return (bool) $this->withTables;
	}



	/**
	 * Define column type.
	 * @param  string  column
	 * @param  string  type (use constant Dibi::*)
	 * @param  string  optional format
	 * @return DibiResult  provides a fluent interface
	 */
	final public function setType($col, $type, $format = NULL)
	{
		$this->xlat[$col] = array('type' => $type, 'format' => $format);
		return $this;
	}



	/**
	 * Autodetect column types.
	 * @return void
	 */
	final public function detectTypes()
	{
		foreach ($this->getInfo()->getColumns() as $col) {
			$this->xlat[$col->getName()] = array('type' => $col->getType(), 'format' => NULL);
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
		$this->xlat = $types;
		return $this;
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
	 * Converts value to specified type and format.
	 * @param  mixed  value
	 * @param  int    type
	 * @param  string format
	 * @return mixed
	 */
	final public function convert($value, $type, $format = NULL)
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
			if ($value == NULL) { // intentionally ==
				return NULL;

			} elseif ($format === NULL) { // return timestamp (default)
				return is_numeric($value) ? (int) $value : strtotime($value);

			} elseif ($format === TRUE) { // return DateTime object
				return new DateTime(is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value);

			} elseif (is_numeric($value)) { // single timestamp
				return date($format, $value);

			} elseif (class_exists('DateTime', FALSE)) { // since PHP 5.2
				$value = new DateTime($value);
				return $value ? $value->format($format) : NULL;

			} else {
				return date($format, strtotime($value));
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
	 * Displays complete result-set as HTML table for debug purposes.
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
