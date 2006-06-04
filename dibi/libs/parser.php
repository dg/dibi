<?php

/**
 * dibi - Database Abstraction Layer according to dgx
 * --------------------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/dibi/
 * @copyright  Copyright (c) 2005-2006 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE
 * @package    dibi
 * @category   Database
 * @version    0.5alpha (2006-05-26) for PHP5
 */


// security - include dibi.php, not this file
if (!defined('dibi')) die();



/**
 * dibi parser
 *
 */
class DibiParser
{
    private
        $modifier,
        $hasError,
        $driver;


    /**
     * Generates SQL
     *
     * @param  array
     * @return string
     */
    public function parse($driver, $args)
    {
        $sql = '';
        $this->driver = $driver;
        $this->modifier = 0;
        $this->hasError = false;
        $command = null;
        $lastString = null;

        foreach ($args as $index => $arg)  {
            $sql .= ' '; // always add simple space


            // array processing (with or without modifier)
            if (is_array($arg)) {
                // determine type:  set | values | list
                if ($this->modifier) {
                    $type = $this->modifier;
                    $this->modifier = false;
                } else {
                    // autodetect
                    if (is_int(key($arg)))
                        $type = 'L'; // LIST
                    else {
                        if (!$command)
                            $command = strtoupper(substr(ltrim($args[0]), 0, 6));

                        $type = $command == 'UPDATE' ? 'S' : 'V'; // SET | VALUES
                    }
                }

                // build array
                $vx = $kx = array();
                switch ($type) {
                case 'S': // SET
                    foreach ($arg as $k => $v)
                        $vx[] = $this->driver->quoteName($k) . '=' . $this->formatValue($v);

                    $sql .= implode(', ', $vx);
                    break;

                case 'V': // VALUES
                    foreach ($arg as $k => $v) {
                        $kx[] = $this->driver->quoteName($k);
                        $vx[] = $this->formatValue($v);
                    }

                    $sql .= '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';
                    break;

                case 'L': // LIST
                    foreach ($arg as $k => $v)
                        $vx[] = $this->formatValue($v);

                    $sql .= implode(', ', $vx);
                    break;

                case 'N': // NAMES
                    foreach ($arg as $v)
                        $vx[] = $this->driver->quoteName($v);

                    $sql .= implode(', ', $vx);
                    break;

                default:
                    $this->hasError = true;
                    $sql .= "**Unknown modifier %$type**";
                }

                continue;
            }



            // after-modifier procession
            if ($this->modifier) {
                if ($arg instanceof IDibiVariable) {
                    $sql .= $arg->toSql($this->driver, $this->modifier);
                    $this->modifier = false;
                    continue;
                }

                if (!is_scalar($arg) && !is_null($arg)) {  // array is already processed
                    $this->hasError = true;
                    $this->modifier = false;
                    $sql .= '**Unexpected '.gettype($arg).'**';
                    continue;
                }

                switch ($this->modifier) {
                case "s":  // string
                    $sql .= $this->driver->escape($arg, TRUE);
                    break;
                case 'T':  // date
                    $sql .= date($this->driver->formats['date'], is_string($arg) ? strtotime($arg) : $arg);
                    break;
                case 't': // datetime
                    $sql .= date($this->driver->formats['datetime'], is_string($arg) ? strtotime($arg) : $arg);
                    break;
                case 'b':  // boolean
                    $sql .= $arg ? $this->driver->formats['TRUE'] : $this->driver->formats['FALSE'];
                    break;
                case 'i':
                case 'u':  // unsigned int
                case 'd':  // signed int
                    $sql .= (string) (int) $arg;
                    break;
                case 'f':  // float
                    $sql .= (string) (float) $arg; // something like -9E-005 is accepted by SQL
                    break;
                case 'n':  // identifier name
                    $sql .= $this->driver->quoteName($arg);
                    break;
                default:
                    $this->hasError = true;
                    $sql .= "**Unknown modifier %$this->modifier**";
                }

                $this->modifier = false;
                continue;
            }


            // simple string means SQL
            if (is_string($arg)) {
                // double string warning
                // (problematic with dibi::queryStart & dibi::queryAdd
//                if ($lastString === $index-1)
//                    trigger_error("Is seems there is error in SQL near '$arg'.", E_USER_WARNING);

                $lastString = $index;

                // speed-up - is regexp required?
                $toSkip = strcspn($arg, '`[\'"%');

                if ($toSkip == strlen($arg)) {
                    $sql .= $arg;
                } else {
                    $sql .= substr($arg, 0, $toSkip)
                         . preg_replace_callback('/
                           (?=`|\[|\'|"|%)              ## speed-up
                           (?:
                              `(.+?)`|                  ## 1) `identifier`
                              \[(.+?)\]|                ## 2) [identifier]
                              (\')((?:\'\'|[^\'])*)\'|  ## 3,4) string
                              (")((?:""|[^"])*)"|       ## 5,6) "string"
                              %([a-zA-Z])$|             ## 7) right modifier
                              (\'|")                    ## 8) lone-quote
                           )/xs',
                           array($this, 'callback'),
                           substr($arg, $toSkip)
                     );
                }

                continue;
            }


            // default processing
            $sql .= $this->formatValue($arg);

        } // for


        if ($this->hasError)
            return new DibiException('Errors during generating SQL', array('sql' => $sql));

        return trim($sql);
    }






    private function formatValue($value)
    {
        if (is_string($value))
            return $this->driver->escape($value, TRUE);

        if (is_int($value) || is_float($value))
            return (string) $value;  // something like -9E-005 is accepted by SQL

        if (is_bool($value))
            return $value ? $this->driver->formats['TRUE'] : $this->driver->formats['FALSE'];

        if (is_null($value))
            return $this->driver->formats['NULL'];

        if ($value instanceof IDibiVariable)
            return $value->toSql($this->driver);

        $this->hasError = true;
        return '**Unsupported type '.gettype($value).'**';
    }





    /**
     * PREG callback for @see self::translate()
     * @param  array
     * @return string
     */
    private function callback($matches)
    {
        //    [1] => `ident`
        //    [2] => [ident]
        //    [3] => '
        //    [4] => string
        //    [5] => "
        //    [6] => string
        //    [7] => right modifier
        //    [8] => lone-quote

        if ($matches[1])  // SQL identifiers: `ident`
            return $this->driver->quoteName($matches[1]);

        if ($matches[2])  // SQL identifiers: [ident]
            return $this->driver->quoteName($matches[2]);

        if ($matches[3])  // SQL strings: '....'
            return $this->driver->escape( strtr($matches[4], array("''" => "'")), true);

        if ($matches[5])  // SQL strings: "..."
            return $this->driver->escape( strtr($matches[6], array('""' => '"')), true);

        if ($matches[7]) { // modifier
            $this->modifier = $matches[7];
            return '';
        }

        if ($matches[8]) { // string quote
            return '**Alone quote**';
            $this->hasError = true;
        }

        die('this should be never executed');
    }





} // class DibiParser







?>