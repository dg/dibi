<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl (http://www.davidgrudl.com)
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
 * dibi SQL translator.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
final class DibiTranslator extends /*Nette::*/Object
{
    /** @var string */
    public $sql;

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
     *
     * @param  array
     * @return bool
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
                $toSkip = strcspn($arg, '`[\'"%');

                if (strlen($arg) === $toSkip) { // needn't be translated
                    $sql[] = $arg;
                } else {
                    $sql[] = substr($arg, 0, $toSkip)
/*
                     . preg_replace_callback('/
                       (?=`|\[|\'|"|%)                 ## speed-up
                       (?:
                          `(.+?)`|                     ## 1) `identifier`
                          \[(.+?)\]|                   ## 2) [identifier]
                          (\')((?:\'\'|[^\'])*)\'|     ## 3,4) string
                          (")((?:""|[^"])*)"|          ## 5,6) "string"
                          (\'|")                       ## 7) lone-quote
                          %([a-zA-Z]{1,4})(?![a-zA-Z])|## 8) modifier
                       )/xs',
*/                  // note: this can change $this->args & $this->cursor & ...
                     . preg_replace_callback('/(?=`|\[|\'|"|%)(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|(\'|")|%([a-zA-Z]{1,4})(?![a-zA-Z]))/s',
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

        // apply limit
        if ($this->limit > -1 || $this->offset > 0) {
            $this->driver->applyLimit($sql, $this->limit, $this->offset);
        }

        $this->sql = $sql;
        return !$this->hasError;
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
        if (is_array($value)) {

            $vx = $kx = array();
            $separator = ', ';
            switch ($modifier) {
            case 'and':
            case 'or':
                $separator = ' ' . strtoupper($modifier) . ' ';
                if (!is_string(key($value))) {
                    foreach ($value as $v) {
                        $vx[] = $this->formatValue($v, 'sql');
                    }
                    return implode($separator, $vx);
                }
                // break intentionally omitted
            case 'a': // SET key=val, key=val, ...
                foreach ($value as $k => $v) {
                    $pair = explode('%', $k, 2); // split into identifier & modifier
                    $vx[] = $this->delimite($pair[0]) . '='
                        . $this->formatValue($v, isset($pair[1]) ? $pair[1] : FALSE);
                }
                return implode($separator, $vx);


            case 'l': // LIST val, val, ...
                foreach ($value as $k => $v) {
                    $pair = explode('%', $k, 2); // split into identifier & modifier
                    $vx[] = $this->formatValue($v, isset($pair[1]) ? $pair[1] : FALSE);
                }
                return '(' . implode(', ', $vx) . ')';


            case 'v': // (key, key, ...) VALUES (val, val, ...)
                foreach ($value as $k => $v) {
                    $pair = explode('%', $k, 2); // split into identifier & modifier
                    $kx[] = $this->delimite($pair[0]);
                    $vx[] = $this->formatValue($v, isset($pair[1]) ? $pair[1] : FALSE);
                }
                return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';

            default:
                foreach ($value as $v) {
                    $vx[] = $this->formatValue($v, $modifier);
                }
                return implode(', ', $vx);
            }
        }


        // with modifier procession
        if ($modifier) {
            if ($value === NULL) {
                return 'NULL';
            }

            if ($value instanceof IDibiVariable) {
                return $value->toSql($this, $modifier);
            }

            if (!is_scalar($value)) {  // array is already processed
                $this->hasError = TRUE;
                return '**Unexpected type ' . gettype($value) . '**';
            }

            switch ($modifier) {
            case 's':  // string
                return $this->driver->format($value, dibi::FIELD_TEXT);

            case 'sn': // string or NULL
                return $value == '' ? 'NULL' : $this->driver->format($value, dibi::FIELD_TEXT); // notice two equal signs

            case 'b':  // boolean
                return $this->driver->format($value, dibi::FIELD_BOOL);

            case 'i':  // signed int
            case 'u':  // unsigned int, ignored
                // support for numbers - keep them unchanged
                if (is_string($value) && preg_match('#[+-]?\d+(e\d+)?$#A', $value)) {
                    return $value;
                }
                return (string) (int) ($value + 0);

            case 'f':  // float
                // support for numbers - keep them unchanged
                if (is_numeric($value) && (!is_string($value) || strpos($value, 'x') === FALSE)) {
                    return $value; // something like -9E-005 is accepted by SQL, HEX values is not
                }
                return (string) ($value + 0);

            case 'd':  // date
                return $this->driver->format(is_string($value) ? strtotime($value) : $value, dibi::FIELD_DATE);

            case 't':  // datetime
                return $this->driver->format(is_string($value) ? strtotime($value) : $value, dibi::FIELD_DATETIME);

            case 'n':  // identifier name
                return $this->delimite($value);

            case 'sql':// preserve as SQL
                $value = (string) $value;
                // speed-up - is regexp required?
                $toSkip = strcspn($value, '`[\'"');
                if (strlen($value) === $toSkip) { // needn't be translated
                    return $value;
                } else {
                    return substr($value, 0, $toSkip)
                     . preg_replace_callback('/(?=`|\[|\'|")(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"(\'|"))/s',
                           array($this, 'cb'),
                           substr($value, $toSkip)
                       );
                }

            case 'a':
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
            return $this->driver->format($value, dibi::FIELD_TEXT);

        if (is_int($value) || is_float($value))
            return (string) $value;  // something like -9E-005 is accepted by SQL

        if (is_bool($value))
            return $this->driver->format($value, dibi::FIELD_BOOL);

        if ($value === NULL)
            return 'NULL';

        if ($value instanceof IDibiVariable)
            return $value->toSql($this, NULL);

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
        //    [8] => modifier (when called from self::translate())

        if (!empty($matches[8])) { // modifier
            $mod = $matches[8];
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
            return $this->driver->format( str_replace("''", "'", $matches[4]), dibi::FIELD_TEXT);

        if ($matches[5])  // SQL strings: "..."
            return $this->driver->format( str_replace('""', '"', $matches[6]), dibi::FIELD_TEXT);

        if ($matches[7]) { // string quote
            $this->hasError = TRUE;
            return '**Alone quote**';
        }

        die('this should be never executed');
    }



    /**
     * Apply substitutions to indentifier and delimites it.
     *
     * @param  string indentifier
     * @return string
     */
    private function delimite($value)
    {
        if (strpos($value, ':') !== FALSE) {
            $value = strtr($value, dibi::getSubst());
        }
        return $this->driver->format($value, dibi::IDENTIFIER);
    }


} // class DibiTranslator
