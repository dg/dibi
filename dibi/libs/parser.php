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
 * @version    $Revision$ $Date$
 */


// security - include dibi.php, not this file
if (!defined('DIBI')) die();



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
        $this->modifier = false;
        $this->hasError = false;
        $command = null;
        //$lastString = null;

        foreach ($args as $index => $arg)  {
            $sql .= ' '; // always add simple space

            // simple string means SQL
            if (is_string($arg) && !$this->modifier) {
                // double string warning
                // (problematic with dibi::queryStart & dibi::queryAdd
//                if ($lastString === $index-1)
//                    trigger_error("Is seems there is error in SQL near '$arg'.", E_USER_WARNING);
//                $lastString = $index;

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

            // array processing without modifier - autoselect between SET or VALUES
            if (is_array($arg) && !$this->modifier && is_string(key($arg))) {
                if (!$command)
                    $command = strtoupper(substr(ltrim($args[0]), 0, 6));

                $this->modifier = ($command == 'INSERT' || $command == 'REPLAC') ? 'V' : 'S';
            }

            // default processing
            $sql .= $this->formatValue($arg, $this->modifier);
            $this->modifier = false;
        } // for


        if ($this->hasError)
            return new DibiException('Errors during generating SQL', array('sql' => $sql));

        return trim($sql);
    }






    private function formatValue($value, $modifier)
    {
        // array processing (with or without modifier)
        if (is_array($value)) {
        
            $vx = $kx = array();
            switch ($modifier) {
            case 'S': // SET
                foreach ($value as $k => $v) {
                    list($k, $mod) = explode('%', $k.'%', 3);  // split modifier
                    $vx[] = $this->driver->quoteName($k) . '=' . $this->formatValue($v, $mod);
                }

                return implode(', ', $vx);

            case 'V': // VALUES
                foreach ($value as $k => $v) {
                    list($k, $mod) = explode('%', $k.'%', 3);  // split modifier
                    $kx[] = $this->driver->quoteName($k);
                    $vx[] = $this->formatValue($v, $mod);
                }

                return '(' . implode(', ', $kx) . ') VALUES (' . implode(', ', $vx) . ')';

            default: // LIST
                foreach ($value as $v)
                    $vx[] = $this->formatValue($v, $modifier);

                return implode(', ', $vx);
            }
        }


        // with modifier procession
        if ($modifier) {
            if ($value instanceof IDibiVariable)
                return $value->toSql($this->driver, $this->modifier);

            if (!is_scalar($value) && !is_null($value)) {  // array is already processed
                $this->hasError = true;
                return '**Unexpected '.gettype($value).'**';
            }

            switch ($modifier) {
            case "s":  // string
                return $this->driver->escape($value, TRUE);
            case 'b':  // boolean
                return $value ? $this->driver->formats['TRUE'] : $this->driver->formats['FALSE'];
            case 'i':
            case 'u':  // unsigned int
            case 'd':  // signed int
                return (string) (int) $value;
            case 'f':  // float
                return (string) (float) $value; // something like -9E-005 is accepted by SQL
            case 'D':  // date
                return date($this->driver->formats['date'], is_string($value) ? strtotime($value) : $value);
            case 'T':  // datetime
                return date($this->driver->formats['datetime'], is_string($value) ? strtotime($value) : $value);
            case 'n':  // identifier name
                return $this->driver->quoteName($value);
            case 'p':  // preserve as SQL
                return (string) $value;
            default:
                $this->hasError = true;
                return "**Unknown modifier %$modifier**";
            }
        }



        // without modifier procession
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
        return '**Unexpected '.gettype($value).'**';
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