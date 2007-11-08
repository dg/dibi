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
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2007 David Grudl
 * @license    http://php7.org/dibi/license  (dibi license)
 * @category   Database
 * @package    Dibi
 * @link       http://php7.org/dibi/
 */



/**
 * dibi result-set abstract class
 *
 * <code>
 * $result = dibi::query('SELECT * FROM [table]');
 * $value = $result->fetchSingle();
 * $all = $result->fetchAll();
 * $assoc = $result->fetchAssoc('id');
 * $assoc = $result->fetchAssoc('active', 'id');
 * unset($result);
 * </code>
 *
 * @version $Revision$ $Date$
 */
abstract class DibiResult extends NObject implements IteratorAggregate, Countable
{
    /**
     * Describes columns types
     * @var array
     */
    protected $convert;

    /**
     * Describes columns types
     * @var array
     */
    protected $meta;


    /**
     * Resultset resource
     * @var resource
     */
    protected $resource;


    private static $types = array(
        dibi::FIELD_TEXT =>    'string',
        dibi::FIELD_BINARY =>  'string',
        dibi::FIELD_BOOL =>    'bool',
        dibi::FIELD_INTEGER => 'int',
        dibi::FIELD_FLOAT =>   'float',
        dibi::FIELD_COUNTER => 'int',
    );




    public function __construct($resource)
    {
        $this->resource = $resource;
    }


    /**
     * Returns the resultset resource
     *
     * @return resource
     */
    final public function getResource()
    {
        return $this->resource;
    }



    /**
     * Moves cursor position without fetching row
     *
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    abstract public function seek($row);



    /**
     * Returns the number of rows in a result set
     *
     * @return int
     */
    abstract public function rowCount();



    /**
     * Frees the resources allocated for this result set
     *
     * @return void
     */
    abstract protected function free();



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    abstract protected function doFetch();



    /**
     * Fetches the row at current position, process optional type conversion
     * and moves the internal cursor to the next position
     *
     * @return array|FALSE  array on success, FALSE if no next record
     */
    final public function fetch()
    {
        $row = $this->doFetch();
        if (!is_array($row)) return FALSE;

        // types-converting?
        if ($t = $this->convert) {  // little speed-up
            foreach ($row as $key => $value) {
                if (isset($t[$key])) {
                    $row[$key] = $this->convert($value, $t[$key]);
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
        $row = $this->doFetch();
        if (!is_array($row)) return FALSE;

        // types-converting?
        if ($t = $this->convert) {  // little speed-up
            $value = reset($row);
            $key = key($row);
            return isset($t[$key])
                ? $this->convert($value, $t[$key])
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
        @$this->seek(0);
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
     * Associative descriptor:  assoc1,*,assoc2,#,assco3
     * builds a tree:           $data[value1][index][value2]['assoc3'][value3] = {record}
     *
     * @param  string  associative descriptor
     * @return array
     * @throws InvalidArgumentException
     */
    final function fetchAssoc($assoc)
    {
        @$this->seek(0);
        $row = $this->fetch();
        if (!$row) return array();  // empty resultset

        $data = NULL;
        $assoc = explode(',', $assoc);

        // check fields
        foreach ($assoc as $as) {
            if ($as !== '*' && $as !== '#' && !array_key_exists($as, $row)) {
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
        if ($assoc[$last] === '#') unset($assoc[$last]);

        // make associative tree
        do {
            $x = & $data;

            // iterative deepening
            foreach ($assoc as $i => $as) {
                if ($as === '*') { // indexed-array node
                    $x = & $x[];

                } elseif ($as === '#') { // "record" node
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
        @$this->seek(0);
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



    /**
     * Automatically frees the resources allocated for this result set
     *
     * @return void
     */
    public function __destruct()
    {
        @$this->free();
    }



    final public function setType($field, $type = NULL)
    {
        if ($field === TRUE) {
            $this->detectTypes();

        } elseif (is_array($field)) {
            $this->convert = $field;

        } else {
            $this->convert[$field] = $type;
        }
    }



    /** is this needed? */
    final public function getType($field)
    {
        return isset($this->convert[$field]) ? $this->convert[$field] : NULL;
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

        if ($type === dibi::FIELD_DATE) {
            return strtotime($value);   // !!! not good
        }

        if ($type === dibi::FIELD_DATETIME) {
            return strtotime($value);  // !!! not good
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
        // lazy init
        if ($this->meta === NULL) {
            $this->buildMeta();
        }
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
        // lazy init
        if ($this->meta === NULL) {
            $this->buildMeta();
        }
        return isset($this->meta[$field]) ? $this->meta[$field] : FALSE;
    }



    /**
     * Acquires ....
     *
     * @return void
     */
    final protected function detectTypes()
    {
        if ($this->meta === NULL) {
            $this->buildMeta();
        }
    }



    /**
     * @return void
     */
    abstract protected function buildMeta();



    /**
     * Displays complete result-set as HTML table
     *
     * @return void
     */
    public function dump()
    {
        echo '<table class="dump">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>#row</th>';
        foreach ($this->getFields() as $field) {
            echo '<th>' . htmlSpecialChars($field) . '</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($this as $row => $fields) {
            echo '<tr><th>', $row, '</th>';
            foreach ($fields as $field) {
                //if (is_object($field)) $field = $field->__toString();
                echo '<td>', htmlSpecialChars($field), '</td>';
            }
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }



    /** these are the required IteratorAggregate functions */
    final public function getIterator($offset = NULL, $count = NULL)
    {
        return new DibiResultIterator($this, $offset, $count);
    }
    /** end required IteratorAggregate functions */



    /** these are the required Countable functions */
    final public function count()
    {
        return $this->rowCount();
    }
    /** end required Countable functions */


}  // class DibiResult








/**
 * Basic Result set iterator.
 *
 * This can be returned by DibiResult::getIterator() method or directly using foreach:
 * <code>
 * $result = dibi::query('SELECT * FROM table');
 * foreach ($result as $fields) {
 *    print_r($fields);
 * }
 * unset($result);
 * </code>
 *
 * Optionally you can specify offset and limit:
 * <code>
 * foreach ($result->getIterator(2, 3) as $fields) {
 *     print_r($fields);
 * }
 * </code>
 */
final class DibiResultIterator implements Iterator
{
    private
        $result,
        $offset,
        $count,
        $row,
        $pointer;


    public function __construct(DibiResult $result, $offset = NULL, $count = NULL)
    {
        $this->result = $result;
        $this->offset = (int) $offset;
        $this->count = $count === NULL ? 2147483647 /*PHP_INT_MAX till 5.0.5 */ : (int) $count;
    }



    /** these are the required Iterator functions */
    public function rewind()
    {
        $this->pointer = 0;
        @$this->result->seek($this->offset);
        $this->row = $this->result->fetch();
    }



    public function key()
    {
        return $this->pointer;
    }



    public function current()
    {
        return $this->row;
    }



    public function next()
    {
        $this->row = $this->result->fetch();
        $this->pointer++;
    }



    public function valid()
    {
        return is_array($this->row) && ($this->pointer < $this->count);
    }
    /** end required Iterator functions */



} // class DibiResultIterator
