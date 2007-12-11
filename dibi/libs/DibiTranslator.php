<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * This source file is subject to the "dibi license" that is bundled
 * with this package in the file license.txt.
 *
 * For more information please see http://dibiphp.com/
 *
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com/
 * @package    dibi
 */



/**
 * dibi SQL translator
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
final class DibiTranslator extends NObject
{
    /** @var string */
    public $sql;

    /** @var string NOT USED YET */
    public $mask;

    /** @var DibiDriverInterface */
    private $driver;

    /** @var string  last modifier */
    private $modifier;

    /** @var bool */
    private $hasError;

    /** @var bool */
    private $comment;

    /** @var int */
    private $ifLevel;

    /** @var int */
    private $ifLevelStart;



    public function __construct(DibiDriverInterface $driver)
    {
        $this->driver = $driver;
    }



    /**
     * Generates SQL
     *
     * @param  array
     * @return bool
     */
    public function translate(array $args)
    {
        $this->hasError = FALSE;
        $commandIns = NULL;
        $lastArr = NULL;
        $mod = & $this->modifier; // shortcut
        $mod = FALSE;

        // conditional sql
        $this->ifLevel = $this->ifLevelStart = 0;
        $comment = & $this->comment;
        $comment = FALSE;

        // iterate
        $sql = $mask = array();
        $i = 0;
        foreach ($args as $arg)
        {
            $i++;

            // %if was opened
            if ($mod === 'if') {
                $mod = FALSE;
                $this->ifLevel++;
                if (!$comment && !$arg) {
                    // open comment
                    $sql[] = "\0";
                    $this->ifLevelStart = $this->ifLevel;
                    $comment = TRUE;
                }
                continue;
            }

            // simple string means SQL
            if (is_string($arg) && (!$mod || $mod === 'sql')) {
                $mod = FALSE;
                // will generate new mod
                /*$mask[] =*/ $sql[] = $this->formatValue($arg, 'sql');
                continue;
            }

            // associative array without modifier - autoselect between SET or VALUES & LIST
            if (!$mod && is_array($arg) && is_string(key($arg))) {
                if ($commandIns === NULL) {
                    $commandIns = strtoupper(substr(ltrim($args[0]), 0, 6));
                    $commandIns = $commandIns === 'INSERT' || $commandIns === 'REPLAC';
                    $mod = $commandIns ? 'v' : 'a';
                } else {
                    $mod = $commandIns ? 'l' : 'a';
                    if ($lastArr === $i - 1) /*$mask[] =*/ $sql[] = ',';
                }
                $lastArr = $i;
            }

            // default processing
            //$mask[] = '?';
            if (!$comment) {
                $sql[] = $this->formatValue($arg, $mod);
            }
            $mod = FALSE;
        } // foreach

        if ($comment) $sql[] = "\0";

        /*$this->mask = implode(' ', $mask);*/

        $this->sql = implode(' ', $sql);

        // remove comments
        // TODO: check !!!
        $this->sql = preg_replace('#\x00.*?\x00#s', '', $this->sql);

        return !$this->hasError;
    }



    /**
     * Apply modifier to single value
     * @param  mixed
     * @param  string
     * @return string
     */
    private function formatValue($value, $modifier)
    {
        // array processing (with or without modifier)
        if (is_array($value)) {

            $vx = $kx = array();
            switch ($modifier) {
            case 'a': // SET (assoc)
                foreach ($value as $k => $v) {
                    // split into identifier & modifier
                    $pair = explode('%', $k, 2);

                    // generate array
                    $vx[] = $this->delimite($pair[0]) . '='
                        . $this->formatValue($v, isset($pair[1]) ? $pair[1] : FALSE);
                }
                return implode(', ', $vx);


            case 'l': // LIST
                $kx = NULL;
            case 'v': // VALUES
                foreach ($value as $k => $v) {
                    // split into identifier & modifier
                    $pair = explode('%', $k, 2);

                    // generate arrays
                    if ($kx !== NULL) {
                        $kx[] = $this->delimite($pair[0]);
                    }
                    $vx[] = $this->formatValue($v, isset($pair[1]) ? $pair[1] : FALSE);
                }

                if ($kx === NULL) {
                    return '(' . implode(', ', $vx) . ')';
                } else {
                    return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';
                }


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

            if ($value instanceof DibiVariableInterface) {
                return $value->toSql($this->driver, $modifier);
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
            case 'p':  // back compatibility
                $value = (string) $value;

                // speed-up - is regexp required?
                $toSkip = strcspn($value, '`[\'"%');

                if (strlen($value) === $toSkip) { // needn't be translated
                    return $value;
                }

                // note: only this can change $this->modifier
                return substr($value, 0, $toSkip)
/*
                     . preg_replace_callback('/
                       (?=`|\[|\'|"|%)              ## speed-up
                       (?:
                          `(.+?)`|                  ## 1) `identifier`
                          \[(.+?)\]|                ## 2) [identifier]
                          (\')((?:\'\'|[^\'])*)\'|  ## 3,4) string
                          (")((?:""|[^"])*)"|       ## 5,6) "string"
                          %(else|end)|              ## 7) conditional SQL
                          %([a-zA-Z]{1,3})$|        ## 8) right modifier
                          (\'|")                    ## 9) lone-quote
                       )/xs',
*/
                     . preg_replace_callback('/(?=`|\[|\'|"|%)(?:`(.+?)`|\[(.+?)\]|(\')((?:\'\'|[^\'])*)\'|(")((?:""|[^"])*)"|%(else|end)|%([a-zA-Z]{1,3})$|(\'|"))/s',
                           array($this, 'cb'),
                           substr($value, $toSkip)
                       );

            case 'a':
            case 'v':
                $this->hasError = TRUE;
                return '**Unexpected type ' . gettype($value) . '**';

            case 'if':
                $this->hasError = TRUE;
                return "**The %$modifier is not allowed here**";

            default:
                $this->hasError = TRUE;
                return "**Unknown modifier %$modifier**";
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

        if ($value instanceof DibiVariableInterface)
            return $value->toSql($this->driver, NULL);

        $this->hasError = TRUE;
        return '**Unexpected ' . gettype($value) . '**';
    }



    /**
     * PREG callback for @see self::formatValue()
     *
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
        //    [7] => %else | %end
        //    [8] => right modifier
        //    [9] => lone-quote

        if (!empty($matches[7])) { // %end | %else
            if (!$this->ifLevel) {
                $this->hasError = TRUE;
                return "**Unexpected condition $matches[7]**";
            }

            if ($matches[7] === 'end') {
                $this->ifLevel--;
                if ($this->ifLevelStart === $this->ifLevel + 1) {
                    // close comment
                    $this->ifLevelStart = 0;
                    $this->comment = FALSE;
                    return "\0";
                }
                return '';
            }

            // else
            if ($this->ifLevelStart === $this->ifLevel) {
                $this->ifLevelStart = 0;
                $this->comment = FALSE;
                return "\0";
            } elseif (!$this->comment) {
                $this->ifLevelStart = $this->ifLevel;
                $this->comment = TRUE;
                return "\0";
            }
        }

        if (!empty($matches[8])) { // modifier
            $this->modifier = $matches[8];
            return '';
        }

        if ($this->comment) return '';


        if ($matches[1])  // SQL identifiers: `ident`
            return $this->delimite($matches[1]);

        if ($matches[2])  // SQL identifiers: [ident]
            return $this->delimite($matches[2]);

        if ($matches[3])  // SQL strings: '....'
            return $this->driver->format( str_replace("''", "'", $matches[4]), dibi::FIELD_TEXT);

        if ($matches[5])  // SQL strings: "..."
            return $this->driver->format( str_replace('""', '"', $matches[6]), dibi::FIELD_TEXT);


        if ($matches[9]) { // string quote
            $this->hasError = TRUE;
            return '**Alone quote**';
        }

        die('this should be never executed');
    }



    /**
     * Apply substitutions to indentifier and delimites it
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
