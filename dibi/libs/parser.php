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
        $this->driver = $driver;
        $mod = & $this->modifier; // shortcut
        $mod = false;
        $this->hasError = false;
        $command = null;

        // conditional sql
        static $condKeys = array('if'=>1, 'else'=>1, 'end'=>1);
        $cond = true;
        $conds = array();

        // iterate
        $sql = array();
        $count = count($args);
        $i= -1;
        while (++$i < $count) {
            $arg = $args[$i];

            // simple string means SQL
            if (is_string($arg) && !$mod) {

                // speed-up - is regexp required?
                $toSkip = strcspn($arg, '`[\'"%');

                if ($toSkip != strlen($arg)) // need be translated?
                    $arg = substr($arg, 0, $toSkip)
                         . preg_replace_callback('/
                           (?=`|\[|\'|"|%)              ## speed-up
                           (?:
                              `(.+?)`|                  ## 1) `identifier`
                              \[(.+?)\]|                ## 2) [identifier]
                              (\')((?:\'\'|[^\'])*)\'|  ## 3,4) string
                              (")((?:""|[^"])*)"|       ## 5,6) "string"
                              %([a-zA-Z]{1,4})$|             ## 7) right modifier
                              (\'|")                    ## 8) lone-quote
                           )/xs',
                           array($this, 'callback'),
                           substr($arg, $toSkip)
                    );

                // add to SQL
                if ($cond) $sql[] = $arg;
                
                // conditional sequence
                if (isset($condKeys[$mod])) {
                    switch ($mod) {
                    case 'if':
                        $conds[] = $cond;
                        $cond = (bool) $args[++$i];
                        break;
                        
                    case 'else':
                        if ($conds) {
                            $cond = !$cond;
                            break;
                        }
                        // no break!

                    case 'end':
                        if ($conds) {
                            $cond = array_pop($conds);
                            break;
                        }
                        
                        $this->hasError = true;
                        $sql[] = "**Unexpected condition $mod**";
                    } // switch
                    
                    $mod = false;
                } // if cond comments
                
                continue;
            }           
            
            // conditional sql (!!! or not?)
            if (!$cond) continue;
            

            // array processing without modifier - autoselect between SET or VALUES
            if (is_array($arg) && !$mod && is_string(key($arg))) {
                if (!$command)
                    $command = strtoupper(substr(ltrim($args[0]), 0, 6));

                $mod = ($command == 'INSERT' || $command == 'REPLAC') ? 'V' : 'S';
            }

            // default processing
            $sql[] = $this->formatValue($arg, $mod);
            $mod = false;
        } // for

        $sql = implode(' ', $sql);

        if ($this->hasError)
            return new DibiException('Errors during generating SQL', array('sql' => $sql));

        return $sql;
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
                return $value->toSql($this->driver, $modifier);

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