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
 * dibi SQL translator.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2009 David Grudl
 * @package    dibi
 */
final class DibiTranslator extends DibiObject
{
	/** @var IDibiDriver */
	private $driver;

	/** @var int */
	private $cursor;

	/** @var array */
	private $args;

	/** @var bool */
	private $hasError;

	/** @var bool */
	private $comment;

	/** @var int */
	private $ifLevel;

	/** @var int */
	private $ifLevelStart;

	/** @var int */
	private $limit;

	/** @var int */
	private $offset;



	public function __construct(IDibiDriver $driver)
	{
		$this->driver = $driver;
	}



	/**
	 * return IDibiDriver.
	 */
	public function getDriver()
	{
		return $this->driver;
	}



	/**
	 * Generates SQL.
	 * @param  array
	 * @return string
	 * @throws DibiException
	 */
	public function translate(array $args)
	{
		$this->limit = -1;
		$this->offset = 0;
		$this->hasError = FALSE;
		$commandIns = NULL;
		$lastArr = NULL;
		// shortcuts
		$cursor = & $this->cursor;
		$cursor = 0;
		$this->args = array_values($args);
		$args = & $this->args;

		// conditional sql
		$this->ifLevel = $this->ifLevelStart = 0;
		$comment = & $this->comment;
		$comment = FALSE;

		// iterate
		$sql = array();
		while ($cursor < count($args))
		{
			$arg = $args[$cursor];
			$cursor++;

			// simple string means SQL
			if (is_string($arg)) {
				// speed-up - is regexp required?
				$toSkip = strcspn($arg, '`[\'":%');

				if (strlen($arg) === $toSkip) { // needn't be translated
					$sql[] = $arg;
				} else {
					$sql[] = substr($arg, 0, $toSkip)
/*
					preg_replace_callback('/
					(?=[`[\'":%?])                    ## speed-up
					(?:
						`(.+?)`|                     ## 1) `identifier`
						\[(.+?)\]|                   ## 2) [identifier]
						(\')((?:\'\'|[^\'])*)\'|     ## 3,4) 'string'
						(")((?:""|[^"])*)"|          ## 5,6) "string"
						(\'|")|                      ## 7) lone quote
						:(\S*?:)([a-zA-Z0-9._]?)|    ## 8,9) substitution
						%([a-zA-Z]{1,4})(?![a-zA-Z]) ## 10) modifier
						(\?)                         ## 11) placeholder
					)/xs',
*/                  // note: this can change $this->args & $this->cursor & ...
					. preg_replace_callback('/(?=[`[\'":%?])(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|:(\S*?:)([a-zA-Z0-9._]?)|%([a-zA-Z]{1,4})(?![a-zA-Z])|(\?))/s',
							array($this, 'cb'),
							substr($arg, $toSkip)
					);

				}
				continue;
			}

			if ($comment) {
				$sql[] = '...';
				continue;
			}

			if ($arg instanceof ArrayObject) {
				$arg = (array) $arg;
			}

			if (is_array($arg)) {
				if (is_string(key($arg))) {
					// associative array -> autoselect between SET or VALUES & LIST
					if ($commandIns === NULL) {
						$commandIns = strtoupper(substr(ltrim($args[0]), 0, 6));
						$commandIns = $commandIns === 'INSERT' || $commandIns === 'REPLAC';
						$sql[] = $this->formatValue($arg, $commandIns ? 'v' : 'a');
					} else {
						if ($lastArr === $cursor - 1) $sql[] = ',';
						$sql[] = $this->formatValue($arg, $commandIns ? 'l' : 'a');
					}
					$lastArr = $cursor;
					continue;

				} elseif ($cursor === 1) {
					// implicit array expansion
					$cursor = 0;
					array_splice($args, 0, 1, $arg);
					continue;
				}
			}

			// default processing
			$sql[] = $this->formatValue($arg, FALSE);
		} // while


		if ($comment) $sql[] = "*/";

		$sql = implode(' ', $sql);

		if ($this->hasError) {
			throw new DibiException('SQL translate error', 0, $sql);
		}

		// apply limit
		if ($this->limit > -1 || $this->offset > 0) {
			$this->driver->applyLimit($sql, $this->limit, $this->offset);
		}

		return $sql;
	}



	/**
	 * Apply modifier to single value.
	 * @param  mixed
	 * @param  string
	 * @return string
	 */
	public function formatValue($value, $modifier)
	{
		// array processing (with or without modifier)
		if ($value instanceof ArrayObject) {
			$value = (array) $value;
		}

		if (is_array($value)) {
			$vx = $kx = array();
			switch ($modifier) {
			case 'and':
			case 'or':  // key=val AND key IS NULL AND ...
				if (empty($value)) {
					return '1=1';
				}

				foreach ($value as $k => $v) {
					if (is_string($k)) {
						$pair = explode('%', $k, 2); // split into identifier & modifier
						$k = $this->delimite($pair[0]) . ' ';
						if (!isset($pair[1])) {
							$v = $this->formatValue($v, FALSE);
							$vx[] = $k . ($v === 'NULL' ? 'IS ' : '= ') . $v;

						} elseif ($pair[1] === 'ex') { // TODO: this will be removed
							$vx[] = $k . $this->formatValue($v, 'ex');

						} else {
							$v = $this->formatValue($v, $pair[1]);
							$vx[] = $k . ($pair[1] === 'l' ? 'IN ' : ($v === 'NULL' ? 'IS ' : '= ')) . $v;
						}

					} else {
						$vx[] = $this->formatValue($v, 'ex');
					}
				}
				return '(' . implode(') ' . strtoupper($modifier) . ' (', $vx) . ')';

			case 'n':  // key, key, ... identifier names
				foreach ($value as $k => $v) {
					if (is_string($k)) {
						$vx[] = $this->delimite($k) . (empty($v) ? '' : ' AS ' . $v);
					} else {
						$pair = explode('%', $v, 2); // split into identifier & modifier
						$vx[] = $this->delimite($pair[0]);
					}
				}
				return implode(', ', $vx);


			case 'a': // key=val, key=val, ...
				foreach ($value as $k => $v) {
					$pair = explode('%', $k, 2); // split into identifier & modifier
					$vx[] = $this->delimite($pair[0]) . '='
						. $this->formatValue($v, isset($pair[1]) ? $pair[1] : (is_array($v) ? 'ex' : FALSE));
				}
				return implode(', ', $vx);


			case 'l': // (val, val, ...)
				foreach ($value as $k => $v) {
					$pair = explode('%', $k, 2); // split into identifier & modifier
					$vx[] = $this->formatValue($v, isset($pair[1]) ? $pair[1] : (is_array($v) ? 'ex' : FALSE));
				}
				return '(' . ($vx ? implode(', ', $vx) : 'NULL') . ')';


			case 'v': // (key, key, ...) VALUES (val, val, ...)
				foreach ($value as $k => $v) {
					$pair = explode('%', $k, 2); // split into identifier & modifier
					$kx[] = $this->delimite($pair[0]);
					$vx[] = $this->formatValue($v, isset($pair[1]) ? $pair[1] : (is_array($v) ? 'ex' : FALSE));
				}
				return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';

			case 'm': // (key, key, ...) VALUES (val, val, ...), (val, val, ...), ...
				foreach ($value as $k => $v) {
					if (is_array($v)) {
						if (isset($proto)) {
							if ($proto !== array_keys($v)) {
								$this->hasError = TRUE;
								return '**Multi-insert array "' . $k . '" is different.**';
							}
						} else {
							$proto = array_keys($v);
						}
					} else {
						$this->hasError = TRUE;
						return '**Unexpected type ' . gettype($v) . '**';
					}

					$pair = explode('%', $k, 2); // split into identifier & modifier
					$kx[] = $this->delimite($pair[0]);
					foreach ($v as $k2 => $v2) {
						$vx[$k2][] = $this->formatValue($v2, isset($pair[1]) ? $pair[1] : (is_array($v2) ? 'ex' : FALSE));
					}
				}
				foreach ($vx as $k => $v) {
					$vx[$k] = '(' . ($v ? implode(', ', $v) : 'NULL') . ')';
				}
				return '(' . implode(', ', $kx) . ') VALUES ' . implode(', ', $vx);

			case 'by': // key ASC, key DESC
				foreach ($value as $k => $v) {
					if (is_string($k)) {
						$v = (is_string($v) && strncasecmp($v, 'd', 1)) || $v > 0 ? 'ASC' : 'DESC';
						$vx[] = $this->delimite($k) . ' ' . $v;
					} else {
						$vx[] = $this->delimite($v);
					}
				}
				return implode(', ', $vx);

			case 'ex':
			case 'sql':
				$translator = new self($this->driver);
				return $translator->translate($value);

			default:  // value, value, value - all with the same modifier
				foreach ($value as $v) {
					$vx[] = $this->formatValue($v, $modifier);
				}
				return $vx ? implode(', ', $vx) : 'NULL';
			}
		}


		// with modifier procession
		if ($modifier) {
			if ($value instanceof IDibiVariable) {
				return $value->toSql($this, $modifier);

			} elseif ($value !== NULL && !is_scalar($value) && !($value instanceof DateTime)) {  // array is already processed
				$this->hasError = TRUE;
				return '**Unexpected type ' . gettype($value) . '**';
			}

			switch ($modifier) {
			case 's':  // string
			case 'bin':// binary
			case 'b':  // boolean
				return $value === NULL ? 'NULL' : $this->driver->escape($value, $modifier);

			case 'sn': // string or NULL
				return $value == '' ? 'NULL' : $this->driver->escape($value, dibi::TEXT); // notice two equal signs

			case 'in': // signed int or NULL
				if ($value == '') $value = NULL;
				// intentionally break omitted

			case 'i':  // signed int
			case 'u':  // unsigned int, ignored
				// support for long numbers - keep them unchanged
				if (is_string($value) && preg_match('#[+-]?\d+(e\d+)?$#A', $value)) {
					return $value;
				} else {
					return $value === NULL ? 'NULL' : (string) (int) ($value + 0);
				}

			case 'f':  // float
				// support for extreme numbers - keep them unchanged
				if (is_string($value) && is_numeric($value) && strpos($value, 'x') === FALSE) {
					return $value; // something like -9E-005 is accepted by SQL, HEX values are not
				} else {
					return $value === NULL ? 'NULL' : rtrim(rtrim(number_format($value, 5, '.', ''), '0'), '.');
				}

			case 'd':  // date
			case 't':  // datetime
				if ($value === NULL) {
					return 'NULL';
				} else {
					if (is_numeric($value)) {
						$value = (int) $value; // timestamp

					} elseif (is_string($value)) {
						$value = class_exists('DateTime', FALSE) ? new DateTime($value) : strtotime($value);
					}
					return $this->driver->escape($value, $modifier);
				}

			case 'by':
			case 'n':  // identifier name
				return $this->delimite($value);

			case 'ex':
			case 'sql': // preserve as dibi-SQL  (TODO: leave only %ex)
				$value = (string) $value;
				// speed-up - is regexp required?
				$toSkip = strcspn($value, '`[\'":');
				if (strlen($value) === $toSkip) { // needn't be translated
					return $value;
				} else {
					return substr($value, 0, $toSkip)
					. preg_replace_callback(
						'/(?=[`[\'":])(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|:(\S*?:)([a-zA-Z0-9._]?))/s',
						array($this, 'cb'),
						substr($value, $toSkip)
					);
				}

			case 'SQL': // preserve as real SQL (TODO: rename to %sql)
				return (string) $value;

			case 'and':
			case 'or':
			case 'a':
			case 'l':
			case 'v':
				$this->hasError = TRUE;
				return '**Unexpected type ' . gettype($value) . '**';

			default:
				$this->hasError = TRUE;
				return "**Unknown or invalid modifier %$modifier**";
			}
		}


		// without modifier procession
		if (is_string($value))
			return $this->driver->escape($value, dibi::TEXT);

		if (is_int($value) || is_float($value))
			return rtrim(rtrim(number_format($value, 5, '.', ''), '0'), '.');

		if (is_bool($value))
			return $this->driver->escape($value, dibi::BOOL);

		if ($value === NULL)
			return 'NULL';

		if ($value instanceof IDibiVariable)
			return $value->toSql($this, NULL);

		if ($value instanceof DateTime)
			return $this->driver->escape($value, dibi::DATETIME);

		$this->hasError = TRUE;
		return '**Unexpected ' . gettype($value) . '**';
	}



	/**
	 * PREG callback from translate() or formatValue().
	 * @param  array
	 * @return string
	 */
	private function cb($matches)
	{
		//    [1] => `ident`
		//    [2] => [ident]
		//    [3] => '
		//    [4] => string
		//    [5] => "
		//    [6] => string
		//    [7] => lone-quote
		//    [8] => substitution
		//    [9] => substitution flag
		//    [10] => modifier (when called from self::translate())
		//    [11] => placeholder (when called from self::translate())


		if (!empty($matches[11])) { // placeholder
			$cursor = & $this->cursor;

			if ($cursor >= count($this->args)) {
				$this->hasError = TRUE;
				return "**Extra placeholder**";
			}

			$cursor++;
			return $this->formatValue($this->args[$cursor - 1], FALSE);
		}

		if (!empty($matches[10])) { // modifier
			$mod = $matches[10];
			$cursor = & $this->cursor;

			if ($cursor >= count($this->args) && $mod !== 'else' && $mod !== 'end') {
				$this->hasError = TRUE;
				return "**Extra modifier %$mod**";
			}

			if ($mod === 'if') {
				$this->ifLevel++;
				$cursor++;
				if (!$this->comment && !$this->args[$cursor - 1]) {
					// open comment
					$this->ifLevelStart = $this->ifLevel;
					$this->comment = TRUE;
					return "/*";
				}
				return '';

			} elseif ($mod === 'else') {
				if ($this->ifLevelStart === $this->ifLevel) {
					$this->ifLevelStart = 0;
					$this->comment = FALSE;
					return "*/";
				} elseif (!$this->comment) {
					$this->ifLevelStart = $this->ifLevel;
					$this->comment = TRUE;
					return "/*";
				}

			} elseif ($mod === 'end') {
				$this->ifLevel--;
				if ($this->ifLevelStart === $this->ifLevel + 1) {
					// close comment
					$this->ifLevelStart = 0;
					$this->comment = FALSE;
					return "*/";
				}
				return '';

			} elseif ($mod === 'ex') { // array expansion
				array_splice($this->args, $cursor, 1, $this->args[$cursor]);
				return '';

			} elseif ($mod === 'lmt') { // apply limit
				if ($this->args[$cursor] !== NULL) $this->limit = (int) $this->args[$cursor];
				$cursor++;
				return '';

			} elseif ($mod === 'ofs') { // apply offset
				if ($this->args[$cursor] !== NULL) $this->offset = (int) $this->args[$cursor];
				$cursor++;
				return '';

			} else { // default processing
				$cursor++;
				return $this->formatValue($this->args[$cursor - 1], $mod);
			}
		}

		if ($this->comment) return '...';

		if ($matches[1])  // SQL identifiers: `ident`
			return $this->delimite($matches[1]);

		if ($matches[2])  // SQL identifiers: [ident]
			return $this->delimite($matches[2]);

		if ($matches[3])  // SQL strings: '...'
			return $this->driver->escape( str_replace("''", "'", $matches[4]), dibi::TEXT);

		if ($matches[5])  // SQL strings: "..."
			return $this->driver->escape( str_replace('""', '"', $matches[6]), dibi::TEXT);

		if ($matches[7]) { // string quote
			$this->hasError = TRUE;
			return '**Alone quote**';
		}

		if ($matches[8]) { // SQL identifier substitution
			$m = substr($matches[8], 0, -1);
			$m = isset(dibi::$substs[$m]) ? dibi::$substs[$m] : call_user_func(dibi::$substFallBack, $m);
			return $matches[9] == '' ? $this->formatValue($m, FALSE) : $m . $matches[9]; // value or identifier
		}

		die('this should be never executed');
	}



	/**
	 * Apply substitutions to indentifier and delimites it.
	 * @param  string indentifier
	 * @return string
	 */
	private function delimite($value)
	{
		if ($value === '*') {
			return '*';

		} elseif (strpos($value, ':') !== FALSE) { // provide substitution
			$value = preg_replace_callback('#:(.*):#U', array(__CLASS__, 'subCb'), $value);
		}

		return $this->driver->escape($value, dibi::IDENTIFIER);
	}



	/**
	 * Substitution callback.
	 * @param  array
	 * @return string
	 */
	private static function subCb($m)
	{
		$m = $m[1];
		return isset(dibi::$substs[$m]) ? dibi::$substs[$m] : call_user_func(dibi::$substFallBack, $m);
	}

}
