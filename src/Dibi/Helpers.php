<?php

/**
 * This file is part of the "dibi" - smart database abstraction layer.
 * Copyright (c) 2005 David Grudl (https://davidgrudl.com)
 */

namespace Dibi;


class Helpers
{
	use Strict;

	/** @var array */
	private static $types;

	/**
	 * Prints out a syntax highlighted version of the SQL command or Result.
	 * @param  string|Result
	 * @param  bool  return output instead of printing it?
	 * @return string
	 */
	public static function dump($sql = NULL, $return = FALSE)
	{
		ob_start();
		if ($sql instanceof Result && PHP_SAPI === 'cli') {
			$hasColors = (substr(getenv('TERM'), 0, 5) === 'xterm');
			$maxLen = 0;
			foreach ($sql as $i => $row) {
				if ($i === 0) {
					foreach ($row as $col => $foo) {
						$len = mb_strlen($col);
						$maxLen = max($len, $maxLen);
					}
				}

				echo $hasColors ? "\033[1;37m#row: $i\033[0m\n" : "#row: $i\n";
				foreach ($row as $col => $val) {
					$spaces = $maxLen - mb_strlen($col) + 2;
					echo "$col" . str_repeat(' ', $spaces) .  "$val\n";
				}
				echo "\n";
			}

			echo empty($row) ? "empty result set\n\n" : "\n";

		} elseif ($sql instanceof Result) {
			foreach ($sql as $i => $row) {
				if ($i === 0) {
					echo "\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";
					foreach ($row as $col => $foo) {
						echo "\t\t<th>" . htmlSpecialChars($col) . "</th>\n";
					}
					echo "\t</tr>\n</thead>\n<tbody>\n";
				}

				echo "\t<tr>\n\t\t<th>", $i, "</th>\n";
				foreach ($row as $col) {
					echo "\t\t<td>", htmlSpecialChars($col), "</td>\n";
				}
				echo "\t</tr>\n";
			}

			echo empty($row)
				? '<p><em>empty result set</em></p>'
				: "</tbody>\n</table>\n";

		} else {
			if ($sql === NULL) {
				$sql = \dibi::$sql;
			}

			static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|FETCH\s+NEXT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE|START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK(?:\s+TO\s+SAVEPOINT)?|(?:RELEASE\s+)?SAVEPOINT';
			static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

			// insert new lines
			$sql = " $sql ";
			$sql = preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

			// reduce spaces
			$sql = preg_replace('#[ \t]{2,}#', ' ', $sql);

			$sql = wordwrap($sql, 100);
			$sql = preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

			// syntax highlight
			$highlighter = "#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is";
			if (PHP_SAPI === 'cli') {
				if (substr(getenv('TERM'), 0, 5) === 'xterm') {
					$sql = preg_replace_callback($highlighter, function ($m) {
						if (!empty($m[1])) { // comment
							return "\033[1;30m" . $m[1] . "\033[0m";

						} elseif (!empty($m[2])) { // error
							return "\033[1;31m" . $m[2] . "\033[0m";

						} elseif (!empty($m[3])) { // most important keywords
							return "\033[1;34m" . $m[3] . "\033[0m";

						} elseif (!empty($m[4])) { // other keywords
							return "\033[1;32m" . $m[4] . "\033[0m";
						}
					}, $sql);
				}
				echo trim($sql) . "\n\n";

			} else {
				$sql = htmlSpecialChars($sql);
				$sql = preg_replace_callback($highlighter, function ($m) {
					if (!empty($m[1])) { // comment
						return '<em style="color:gray">' . $m[1] . '</em>';

					} elseif (!empty($m[2])) { // error
						return '<strong style="color:red">' . $m[2] . '</strong>';

					} elseif (!empty($m[3])) { // most important keywords
						return '<strong style="color:blue">' . $m[3] . '</strong>';

					} elseif (!empty($m[4])) { // other keywords
						return '<strong style="color:green">' . $m[4] . '</strong>';
					}
				}, $sql);
				echo '<pre class="dump">', trim($sql), "</pre>\n\n";
			}
		}

		if ($return) {
			return ob_get_clean();
		} else {
			ob_end_flush();
		}
	}


	/**
	 * Finds the best suggestion.
	 * @return string|NULL
	 * @internal
	 */
	public static function getSuggestion(array $items, $value)
	{
		$best = NULL;
		$min = (strlen($value) / 4 + 1) * 10 + .1;
		foreach (array_unique($items, SORT_REGULAR) as $item) {
			$item = is_object($item) ? $item->getName() : $item;
			if (($len = levenshtein($item, $value, 10, 11, 10)) > 0 && $len < $min) {
				$min = $len;
				$best = $item;
			}
		}
		return $best;
	}


	/** @internal */
	public static function escape(Driver $driver, $value, $type)
	{
		static $types = [
			Type::TEXT => 'text',
			Type::BINARY => 'binary',
			Type::BOOL => 'bool',
			Type::DATE => 'date',
			Type::DATETIME => 'datetime',
			\dibi::IDENTIFIER => 'identifier',
			Type::JSONB => 'jsonb',
			Type::JSON => 'json',
			Type::ARRAY_TYPE => 'array'
		];
		if (isset($types[$type])) {
			return $driver->{'escape' . $types[$type]}($value);
		} else {
			throw new \InvalidArgumentException('Unsupported type.');
		}
	}


	/**
	 * Heuristic type detection.
	 * @param  string
	 * @return string|NULL
	 * @internal
	 */
	public static function detectType($type)
	{
		static $patterns = [
			'^(_INT4|_INT2|_INT8|_CHAR|_VARCHAR|_TEXT)' => Type::ARRAY_TYPE,
			'^_' => Type::TEXT, // PostgreSQL arrays
			'BYTEA|BLOB|BIN' => Type::BINARY,
			'TEXT|CHAR|POINT|INTERVAL|STRING' => Type::TEXT,
			'YEAR|BYTE|COUNTER|SERIAL|INT|LONG|SHORT|^TINY$' => Type::INTEGER,
			'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER' => Type::FLOAT,
			'^TIME$' => Type::TIME,
			'TIME' => Type::DATETIME, // DATETIME, TIMESTAMP
			'DATE' => Type::DATE,
			'BOOL' => Type::BOOL,
			'JSONB' => Type::JSONB,
			'JSON' => Type::JSON,
		];

		foreach ($patterns as $s => $val) {
			if (preg_match("#$s#i", $type)) {
				return $val;
			}
		}
		return NULL;
	}


	/**
	 * @internal
	 */
	public static function getTypeCache()
	{
		if (self::$types === NULL) {
			self::$types = new HashMap([__CLASS__, 'detectType']);
		}
		return self::$types;
	}


	/**
	 * Apply configuration alias or default values.
	 * @param  array  connect configuration
	 * @param  string key
	 * @param  string alias key
	 * @return void
	 */
	public static function alias(& $config, $key, $alias)
	{
		$foo = & $config;
		foreach (explode('|', $key) as $key) {
			$foo = & $foo[$key];
		}

		if (!isset($foo) && isset($config[$alias])) {
			$foo = $config[$alias];
			unset($config[$alias]);
		}
	}


	/**
	 * Import SQL dump from file.
	 * @return int  count of sql commands
	 */
	public static function loadFromFile(Connection $connection, $file)
	{
		@set_time_limit(0); // intentionally @

		$handle = @fopen($file, 'r'); // intentionally @
		if (!$handle) {
			throw new \RuntimeException("Cannot open file '$file'.");
		}

		$count = 0;
		$delimiter = ';';
		$sql = '';
		$driver = $connection->getDriver();
		while (!feof($handle)) {
			$s = rtrim(fgets($handle));
			if (substr($s, 0, 10) === 'DELIMITER ') {
				$delimiter = substr($s, 10);

			} elseif (substr($s, -strlen($delimiter)) === $delimiter) {
				$sql .= substr($s, 0, -strlen($delimiter));
				$driver->query($sql);
				$sql = '';
				$count++;

			} else {
				$sql .= $s . "\n";
			}
		}
		if (trim($sql) !== '') {
			$driver->query($sql);
			$count++;
		}
		fclose($handle);
		return $count;
	}

	/**
	 * Parses PostgreSQL Array Type into PHP Array
	 *
	 * Source: http://stackoverflow.com/questions/3068683/convert-postgresql-array-to-php-array
	 *
	 * @param $s
	 * @param int $start
	 * @param null $end
	 * @return array|null
	 */
	public static function pgArrayParse($s, $start=0, &$end=NULL)
	{
		if (empty($s) || $s[0]!='{') return NULL;
		$return = array();
		$string = false;
		$quote='';
		$len = strlen($s);
		$v = '';
		for($i = $start + 1; $i < $len; $i++) {
			$ch = $s[$i];

			if (!$string && $ch == '}'){
				if ($v !== '' || !empty($return)) {
					$return[] = $v;
				}
				$end = $i;
				break;
			} elseif (!$string && $ch == '{') {
				$v = static::pgArrayParse($s, $i, $i);
			} elseif (!$string && $ch == ','){
				$return[] = $v;
				$v = '';
			} elseif (!$string && ($ch == '"' || $ch == "'")) {
				$string = TRUE;
				$quote = $ch;
			} elseif ($string && $ch == $quote && $s[$i-1] == "\\") {
				$v = substr($v,0,-1).$ch;
			} elseif ($string && $ch == $quote && $s[$i-1] != "\\") {
				$string = FALSE;
			} else {
				$v .= $ch;
			}
		}
		return $return;
	}

	/**
	 * Converts a php array into a postgres array (also multidimensional)
	 *
	 * Each element is escaped using pg_escape_string, only string values
	 * are enclosed within single quotes, numeric values no; special
	 * elements as php nulls or booleans are literally converted, so the
	 * php NULL value is written literally 'NULL' and becomes a postgres
	 * NULL (the same thing is done with TRUE and FALSE values).
	 *
	 * Source: http://stackoverflow.com/questions/5631387/php-array-to-postgres-array
	 *
	 * Examples :
	 * VARCHAR VERY BASTARD ARRAY :
	 *    $input = array('bla bla', 'ehi "hello"', 'abc, def', ' \'VERY\' "BASTARD,\'value"', NULL);
	 *
	 *    Helpers::pgArrayCreate($input) ==>> 'ARRAY['bla bla','ehi "hello"','abc, def',' ''VERY'' "BASTARD,''value"',NULL]'
	 *
	 *    try to put this value in a query (you will get a valid result):
	 *    select unnest(ARRAY['bla bla','ehi "hello"','abc, def',' ''VERY'' "BASTARD,''value"',NULL]::varchar[])
	 *
	 * NUMERIC ARRAY:
	 *    $input = array(1, 2, 3, 8.5, null, 7.32);
	 *    Helpers::pgArrayCreate($input) ==>> 'ARRAY[1,2,3,8.5,NULL,7.32]'
	 *    try: select unnest(ARRAY[1,2,3,8.5,NULL,7.32]::numeric[])
	 *
	 * BOOLEAN ARRAY:
	 *    $input = array(false, true, true, null);
	 *    Helpers::pgArrayCreate($input) ==>> 'ARRAY[FALSE,TRUE,TRUE,NULL]'
	 *    try: select unnest(ARRAY[FALSE,TRUE,TRUE,NULL]::boolean[])
	 *
	 * MULTIDIMENSIONAL ARRAY:
	 *    $input = array(array('abc', 'def'), array('ghi', 'jkl'));
	 *    Helpers::pgArrayCreate($input) ==>> 'ARRAY[ARRAY['abc','def'],ARRAY['ghi','jkl']]'
	 *    try: select ARRAY[ARRAY['abc','def'],ARRAY['ghi','jkl']]::varchar[][]
	 *
	 * EMPTY ARRAY (is different than null!!!):
	 *    $input = array();
	 *    Helpers::pgArrayCreate($input) ==>> 'ARRAY[]'
	 *    try: select unnest(ARRAY[]::varchar[])
	 *
	 * NULL VALUE :
	 *    $input = NULL;
	 *    Helpers::pgArrayCreate($input) ==>> 'NULL'
	 *    the functions returns a string='NULL' (literally 'NULL'), so putting it
	 *    in the query, it becomes a postgres null value.
	 *
	 * If you pass a value that is not an array, the function returns a literal 'NULL'.
	 *
	 * You should put the result of this functions directly inside a query,
	 * without quoting or escaping it and you cannot use this result as parameter
	 * of a prepared statement.
	 *
	 * Example:
	 * $q = 'INSERT INTO foo (field1, field_array) VALUES ($1, ' . Helpers::pgArrayCreate($php_array) . '::varchar[])';
	 * $params = array('scalar_parameter');
	 *
	 * It is recommended to write the array type (ex. varchar[], numeric[], ...)
	 * because if the array is empty or contains only null values, postgres
	 * can give an error (cannot determine type of an empty array...)
	 *
	 * The function returns only a syntactically well-formed array, it does not
	 * make any logical check, you should consider that postgres gives errors
	 * if you mix different types (ex. numeric and text) or different dimensions
	 * in a multidim array.
	 *
	 * @param array $set PHP array
	 *
	 * @return string Array in postgres syntax
	 */
	public static function pgArrayCreate($set) {

		if (is_null($set) || !is_array($set)) {
			return 'NULL';
		}

		// can be called with a scalar or array
		settype($set, 'array');

		$result = array();
		foreach ($set as $t) {
			// Element is array : recursion
			if (is_array($t)) {
				$result[] = static::pgArrayCreate($t);
			}
			else {
				// PHP NULL
				if (is_null($t)) {
					$result[] = 'NULL';
				}
				// PHP TRUE::boolean
				elseif (is_bool($t) && $t == TRUE) {
					$result[] = 'TRUE';
				}
				// PHP FALSE::boolean
				elseif (is_bool($t) && $t == FALSE) {
					$result[] = 'FALSE';
				}
				// Other scalar value
				else {
					// Escape
					$t = pg_escape_string($t);

					// quote only non-numeric values
					if (!is_numeric($t)) {
						$t = '"' . $t . '"';
						//$t = $t;
					}
					$result[] = $t;
				}
			}
		}
		return '{' . implode(",", $result) . '}'; // PostgeSQL format
		//return 'ARRAY[' . implode(",", $result) . ']'; // ANSI format
	}

}
