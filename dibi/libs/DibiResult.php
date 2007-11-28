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
 * For more information please see http://php7.org/dibi/
 *
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  dibi license
 * @link       http://php7.org/dibi/
 * @package    dibi
 */



/**
 * dibi result-set abstract class
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
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
class DibiResult extends NObject implements IteratorAggregate, Countable
{
    /**
     * DibiDriverInterface
     * @var array
     */
    private $driver;

    /**
     * Translate table
     * @var array
     */
    private $xlat;

    /**
     * Describes columns types
     * @var array
     */
    private $meta;

    /**
     * Already fetched? Used for allowance for first seek(0)
     * @var bool
     */
    private $fetched = FALSE;



    private static $types = array(
        dibi::FIELD_TEXT =>    'string',
        dibi::FIELD_BINARY =>  'string',
        dibi::FIELD_BOOL =>    'bool',
        dibi::FIELD_INTEGER => 'int',
        dibi::FIELD_FLOAT =>   'float',
        dibi::FIELD_COUNTER => 'int',
    );




    public function __construct($driver)
    {
        $this->driver = $driver;
    }


    /**
     * Automatically frees the resources allocated for this result set
     *
     * @return void
     */
    public function __destruct()
    {
        @$this->free();
    }



    /**
     * Returns the resultset resource
     *
     * @return mixed
     */
    final public function getResource()
    {
        return $this->getDriver()->getResultResource();
    }



    /**
     * Moves cursor position without fetching row
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
     * Returns the number of rows in a result set
     *
     * @return int
     */
    final public function rowCount()
    {
        return $this->getDriver()->rowCount();
    }



    /**
     * Frees the resources allocated for this result set
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
     * Fetches the row at current position, process optional type conversion
     * and moves the internal cursor to the next position
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    final public function fetch()
    {
        $row = $this->getDriver()->fetch();
        if (!is_array($row)) return FALSE;
        $this->fetched = TRUE;

        // types-converting?
        if ($this->xlat) {
            $xlat = $this->xlat; // little speed-up
            foreach ($row as $key => $value) {
                if (isset($xlat[$key])) {
                    $row[$key] = $this->convert($value, $xlat[$key]);
                }
            }
        }

        return $row;
    }



    /**
     * Like fetch(), but returns only first field
     *
     * @return mixed  value on success, FALSE if no next record
     */
    final function fetchSingle()
    {
        $row = $this->getDriver()->fetch();
        if (!is_array($row)) return FALSE;
        $this->fetched = TRUE;

        // types-converting?
        if ($this->xlat) {
            $xlat = $this->xlat; // little speed-up
            $value = reset($row);
            $key = key($row);
            return isset($xlat[$key])
                ? $this->convert($value, $xlat[$key])
                : $value;
        }

        return reset($row);
    }



    /**
     * Fetches all records from table.
     *
     * @return array
     */
    final function fetchAll()
    {
        $this->seek(0);
        $row = $this->fetch();
        if (!$row) return array();  // empty resultset

        $data = array();
        if (count($row) === 1) {
            $key = key($row);
            do {
                $data[] = $row[$key];
            } while ($row = $this->fetch());

        } else {

            do {
                $data[] = $row;
            } while ($row = $this->fetch());
        }

        return $data;
    }



    /**
     * Fetches all records from table and returns associative tree
     * Associative descriptor:  assoc1,#,assoc2,=,assco3
     * builds a tree:           $data[value1][index][value2]['assoc3'][value3] = {record}
     *
     * @param  string  associative descriptor
     * @return array
     * @throws InvalidArgumentException
     */
    final function fetchAssoc($assoc)
    {
        $this->seek(0);
        $row = $this->fetch();
        if (!$row) return array();  // empty resultset

        $data = NULL;
        $assoc = explode(',', $assoc);

        // check fields
        foreach ($assoc as $as) {
            if ($as !== '#' && $as !== '=' && !array_key_exists($as, $row)) {
                throw new InvalidArgumentException("Unknown column '$as' in associative descriptor");
            }
        }

        if (count($assoc) === 1) {  // speed-up
            $as = $assoc[0];
            do {
                $data[ $row[$as] ] = $row;
            } while ($row = $this->fetch());
            return $data;
        }

        $last = count($assoc) - 1;
        if ($assoc[$last] === '=') unset($assoc[$last]);

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

                } else { // associative-array node
                    $x = & $x[ $row[ $as ] ];
                }
            }

            if ($x === NULL) $x = $row; // build leaf

        } while ($row = $this->fetch());

        unset($x);
        return $data;
    }



    /**
     * Fetches all records from table like $key => $value pairs
     *
     * @param  string  associative key
     * @param  string  value
     * @return array
     * @throws InvalidArgumentException
     */
    final function fetchPairs($key = NULL, $value = NULL)
    {
        $this->seek(0);
        $row = $this->fetch();
        if (!$row) return array();  // empty resultset

        $data = array();

        if ($value === NULL) {
            if ($key !== NULL) {
                throw new InvalidArgumentException("Either none or both fields must be specified");
            }

            if (count($row) < 2) {
                throw new LoginException("Result must have at least two columns");
            }

            // autodetect
            $tmp = array_keys($row);
            $key = $tmp[0];
            $value = $tmp[1];

        } else {
            if (!array_key_exists($value, $row)) {
                throw new InvalidArgumentException("Unknown value column '$value'");
            }

            if ($key === NULL) { // indexed-array
                do {
                    $data[] = $row[$value];
                } while ($row = $this->fetch());
                return $data;
            }

            if (!array_key_exists($key, $row)) {
                throw new InvalidArgumentException("Unknown key column '$key'");
            }
        }

        do {
            $data[ $row[$key] ] = $row[$value];
        } while ($row = $this->fetch());

        return $data;
    }



    final public function setType($field, $type = NULL)
    {
        if ($field === TRUE) {
            $this->buildMeta();

        } elseif (is_array($field)) {
            $this->xlat = $field;

        } else {
            $this->xlat[$field] = $type;
        }
    }



    /** is this needed? */
    final public function getType($field)
    {
        return isset($this->xlat[$field]) ? $this->xlat[$field] : NULL;
    }



    final public function convert($value, $type)
    {
        if ($value === NULL || $value === FALSE) {
            return $value;
        }

        if (isset(self::$types[$type])) {
            settype($value, self::$types[$type]);
            return $value;
        }

        if ($type === dibi::FIELD_DATE || $type === dibi::FIELD_DATETIME) {
            return strtotime($value);   // !!! not good
        }

        return $value;
    }



    /**
     * Gets an array of field names
     *
     * @return array
     */
    final public function getFields()
    {
        $this->buildMeta();
        return array_keys($this->meta);
    }



    /**
     * Gets an array of meta informations about column
     *
     * @param  string  column name
     * @return array
     */
    final public function getMetaData($field)
    {
        $this->buildMeta();
        return isset($this->meta[$field]) ? $this->meta[$field] : FALSE;
    }



    /**
     * Acquires ....
     *
     * @return void
     */
    final protected function buildMeta()
    {
        if ($this->meta === NULL) {
            $this->meta = $this->getDriver()->buildMeta();
            foreach ($this->meta as $name => $info) {
                $this->xlat[$name] = $info['type'];
            }
        }
    }



    /**
     * Displays complete result-set as HTML table for debug purposes
     *
     * @return void
     */
    final public function dump()
    {
        echo "\n<table class=\"dump\">\n<thead>\n\t<tr>\n\t\t<th>#row</th>\n";

        foreach ($this->getFields() as $field) {
            echo "\t\t<th>" . htmlSpecialChars($field) . "</th>\n";
        }

        echo "\t</tr>\n</thead>\n<tbody>\n";

        foreach ($this as $row => $fields) {
            echo "\t<tr>\n\t\t<th>", $row, "</th>\n";
            foreach ($fields as $field) {
                //if (is_object($field)) $field = $field->__toString();
                echo "\t\t<td>", htmlSpecialChars($field), "</td>\n";
            }
            echo "\t</tr>\n";
        }

        echo "</tbody>\n</table>\n";
    }



    /**
     * Required by the IteratorAggregate interface
     * @param int  offset
     * @param int  limit
     * @return ArrayIterator
     */
    final public function getIterator($offset = NULL, $limit = NULL)
    {
        return new DibiResultIterator($this, $offset, $limit);
    }



    /**
     * Required by the Countable interface
     * @return int
     */
    final public function count()
    {
        return $this->rowCount();
    }



    /**
     * Safe access to property $driver
     *
     * @return DibiDriverInterface
     * @throws DibiException
     */
    private function getDriver()
    {
        if ($this->driver === NULL) {
            throw new DibiException('Resultset was released from memory');
        }

        return $this->driver;
    }


}
