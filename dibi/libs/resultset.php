<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * @version  $Revision$ $Date$
 * @package  dibi
 */


// security - include dibi.php, not this file
if (!class_exists('dibi', FALSE)) die();



// PHP < 5.1 compatibility
if (!interface_exists('Countable', false)) {
    interface Countable
    {
        function count();
    }
}



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
 */
abstract class DibiResult implements IteratorAggregate, Countable
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


    static private $types = array(
        dibi::FIELD_TEXT =>    'string',
        dibi::FIELD_BINARY =>  'string',
        dibi::FIELD_BOOL =>    'bool',
        dibi::FIELD_INTEGER => 'int',
        dibi::FIELD_FLOAT =>   'float',
        dibi::FIELD_COUNTER => 'int',
    );



    /**
     * Moves cursor position without fetching row
     * @param  int      the 0-based cursor pos to seek to
     * @return boolean  TRUE on success, FALSE if unable to seek to specified record
     */
    abstract public function seek($row);



    /**
     * Returns the number of rows in a result set
     * @return int
     */
    abstract public function rowCount();



    /**
     * Frees the resources allocated for this result set
     * @return void
     */
    abstract protected function free();



    /**
     * Fetches the row at current position and moves the internal cursor to the next position
     * internal usage only
     * @return array|FALSE  array() on success, FALSE if no next record
     */
    abstract protected function doFetch();



    /**
     * Fetches the row at current position, process optional type conversion
     * and moves the internal cursor to the next position
     * @return array|FALSE  array() on success, FALSE if no next record
     */
    final public function fetch()
    {
        $rec = $this->doFetch();
        if (!is_array($rec))
            return FALSE;

        // types-converting?
        if ($t = $this->convert) {  // little speed-up
            foreach ($rec as $key => $value) {
                if (isset($t[$key]))
                    $rec[$key] = $this->convert($value, $t[$key]);
            }
        }

        return $rec;
    }



    /**
     * Like fetch(), but returns only first field
     * @return mixed  value on success, FALSE if no next record
     */
    final function fetchSingle()
    {
        $rec = $this->doFetch();
        if (!is_array($rec))
            return FALSE;

        // types-converting?
        if ($t = $this->convert) {  // little speed-up
            $value = reset($rec);
            $key = key($rec);
            return isset($t[$key])
                ? $this->convert($value, $t[$key])
                : $value;
        }

        return reset($rec);
    }



    /**
     * Fetches all records from table.
     * @return array
     */
    final function fetchAll()
    {
        @$this->seek(0);
        $rec = $this->fetch();
        if (!$rec)
            return array();  // empty resultset

        $arr = array();
        if (count($rec) === 1) {
            $key = key($rec);
            do {
                $arr[] = $rec[$key];
            } while ($rec = $this->fetch());

        } else {

            do {
                $arr[] = $rec;
            } while ($rec = $this->fetch());
        }

        return $arr;
    }



    /**
     * Fetches all records from table and returns associative tree
     * Associative descriptor:  assoc1,*,assoc2,#,assco3
     * builds a tree:           $arr[value1][index][value2]['assoc3'][value3] = {record}
     *
     * @param  string  associative descriptor
     * @return array
     */
    final function fetchAssoc($assoc)
    {
        @$this->seek(0);
        $rec = $this->fetch();
        if (!$rec) return array();  // empty resultset

        $arr = NULL;
        $assoc = explode(',', $assoc);

        if (count($assoc) === 1) {  // speed-up
            $as = $assoc[0];
            do {
                $arr[ $rec[$as] ] = $rec;
            } while ($rec = $this->fetch());
            return $arr;
        }

        $last = count($assoc) - 1;
        if ($assoc[$last] === '#') unset($assoc[$last]);

        // make associative tree
        do {
            $x = & $arr;

            // iterative deepening
            foreach ($assoc as $i => $as) {
                if ($as === '*') { // indexed-array node
                    $x = & $x[];

                } elseif ($as === '#') { // "record" node
                    if ($x === NULL) {
                        $x = $rec;
                        $x = & $x[ $assoc[$i+1] ];
                        $x = NULL; // prepare child node
                    } else {
                        $x = & $x[ $assoc[$i+1] ];
                    }

                } else { // associative-array node
                    $x = & $x[ $rec[ $as ] ];
                }
            }

            if ($x === NULL) $x = $rec; // build leaf

        } while ($rec = $this->fetch());

        unset($x);
        return $arr;
    }



    /**
     * Fetches all records from table like $key => $value pairs
     * @param  string  associative key
     * @param  string  value
     * @return array
     */
    final function fetchPairs($key=NULL, $value=NULL)
    {
        @$this->seek(0);
        $rec = $this->fetch();
        if (!$rec) return array();  // empty resultset

        $arr = array();

        if ($value === NULL) {
            if ($key !== NULL) return FALSE; // error

            // autodetect
            if (count($rec) < 2) return FALSE;
            $tmp = array_keys($rec);
            $key = $tmp[0];
            $value = $tmp[1];

        } else {
            if (!array_key_exists($value, $rec)) return FALSE;

            if ($key === NULL) { // autodetect
                do {
                    $arr[] = $rec[$value];
                } while ($rec = $this->fetch());
                return $arr;
            }

            if (!array_key_exists($key, $rec)) return FALSE;
        }

        do {
            $arr[ $rec[$key] ] = $rec[$value];
        } while ($rec = $this->fetch());

        return $arr;
    }



    /**
     * Automatically frees the resources allocated for this result set
     * @return void
     */
    public function __destruct()
    {
        @$this->free();
    }



    public function setType($field, $type = NULL)
    {
        if ($field === TRUE)
            $this->detectTypes();

        elseif (is_array($field))
            $this->convert = $field;

        else
            $this->convert[$field] = $type;
    }



    /** is this needed? */
    public function getType($field)
    {
        return isset($this->convert[$field]) ? $this->convert[$field] : NULL;
    }



    public function convert($value, $type)
    {
        if ($value === NULL || $value === FALSE)
            return $value;

        if (isset(self::$types[$type])) {
            settype($value, self::$types[$type]);
            return $value;
        }

        if ($type === dibi::FIELD_DATE)
            return strtotime($value);   // !!! not good

        if ($type === dibi::FIELD_DATETIME)
            return strtotime($value);  // !!! not good

        return $value;
    }



    /**
     * Gets an array of field names
     * @return array
     */
    public function getFields()
    {
        // lazy init
        if ($this->meta === NULL) $this->buildMeta();
        return array_keys($this->meta);
    }



    /**
     * Gets an array of meta informations about column
     * @param  string  column name
     * @return array
     */
    public function getMetaData($field)
    {
        // lazy init
        if ($this->meta === NULL) $this->buildMeta();
        return isset($this->meta[$field]) ? $this->meta[$field] : FALSE;
    }



    /**
     * Acquires ....
     * @return void
     */
    protected function detectTypes()
    {
        if ($this->meta === NULL) $this->buildMeta();
    }



    /**
     * @return void
     */
    abstract protected function buildMeta();



    /** these are the required IteratorAggregate functions */
    public function getIterator($offset = NULL, $count = NULL)
    {
        return new DibiResultIterator($this, $offset, $count);
    }
    /** end required IteratorAggregate functions */



    /** these are the required Countable functions */
    public function count()
    {
        return $this->rowCount();
    }
    /** end required Countable functions */



    /**
     * Access to undeclared property
     */
    function __get($nm) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$nm"); }
    function __set($nm, $val) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$nm"); }
    function __unset($nm) { throw new Exception("Access to undeclared property: " . get_class($this) . "::$$nm"); }

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
class DibiResultIterator implements Iterator
{
    private
        $result,
        $offset,
        $count,
        $record,
        $row;


    public function __construct(DibiResult $result, $offset = NULL, $count = NULL)
    {
        $this->result = $result;
        $this->offset = (int) $offset;
        $this->count = $count === NULL ? 2147483647 /*PHP_INT_MAX till 5.0.5 */ : (int) $count;
    }



    /** these are the required Iterator functions */
    public function rewind()
    {
        $this->row = 0;
        @$this->result->seek($this->offset);
        $this->record = $this->result->fetch();
    }



    public function key()
    {
        return $this->row;
    }



    public function current()
    {
        return $this->record;
    }



    public function next()
    {
        $this->record = $this->result->fetch();
        $this->row++;
    }



    public function valid()
    {
        return is_array($this->record) && ($this->row < $this->count);
    }
    /** end required Iterator functions */



} // class DibiResultIterator
