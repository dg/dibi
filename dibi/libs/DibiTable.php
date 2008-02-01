<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * Copyright (c) 2005, 2008 David Grudl aka -dgx- (http://www.dgx.cz)
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
 * Experimental object-oriented interface to database tables.
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005, 2008 David Grudl
 * @package    dibi
 * @version    $Revision$ $Date$
 */
abstract class DibiTable extends NObject
{
    /** @var string  primary key mask */
    public static $primaryMask = 'id';

    /** @var bool */
    public static $lowerCase = TRUE;

    /** @var DibiConnection */
    private $connection;

    /** @var array */
    private $options;

    /** @var string  table name */
    protected $name;

    /** @var string  primary key name */
    protected $primary;

    /** @var string  primary key type */
    protected $primaryModifier = '%i';

    /** @var array */
    protected $blankRow = array();


    /**
     * Table constructor.
     * @param  array
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;

        $this->setup();

        if ($this->connection === NULL) {
            $this->connection = dibi::getConnection();
        }
    }



    /**
     * Returns the table name.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }



    /**
     * Returns the primary key name.
     * @return string
     */
    public function getPrimary()
    {
        return $this->primary;
    }



    /**
     * Returns the dibi connection.
     * @return DibiConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }



    /**
     * Setup object.
     * @return void
     */
    protected function setup()
    {
        // autodetect table name
        if ($this->name === NULL) {
            $name = $this->getClass();
            if (FALSE !== ($pos = strrpos($name, ':'))) {
                $name = substr($name, $pos + 1);
            }
            if (self::$lowerCase) {
                $name = strtolower($name);
            }
            $this->name = $name;
        }

        // autodetect primary key name
        if ($this->primary === NULL) {
            $this->primary = str_replace(
                array('%p', '%s'),
                array($this->name, trim($this->name, 's')), // the simplest inflector in the world :-))
                self::$primaryMask
            );
        }
    }



    /**
     * Inserts row into a table.
     * @param  array|object
     * @return int  new primary key
     */
    public function insert($data)
    {
        $this->connection->query(
            'INSERT INTO %n', $this->name, '%v', $this->prepare($data)
        );
        return $this->connection->insertId();
    }



    /**
     * Updates rows in a table.
     * @param  mixed  primary key value(s)
     * @param  array|object
     * @return int    number of updated rows
     */
    public function update($where, $data)
    {
        $this->connection->query(
            'UPDATE %n', $this->name,
            'SET %a', $this->prepare($data),
            'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $where, ')'
        );
        return $this->connection->affectedRows();
    }



    /**
     * Deletes rows from a table by primary key.
     * @param  mixed  primary key value(s)
     * @return int    number of deleted rows
     */
    public function delete($where)
    {
        $this->connection->query(
            'DELETE FROM %n', $this->name,
            'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $where, ')'
        );
        return $this->connection->affectedRows();
    }



    /**
     * Finds rows by primary key.
     * @param  mixed  primary key value(s)
     * @return DibiResult
     */
    public function find($what)
    {
        if (!is_array($what)) {
            $what = func_get_args();
        }
        return $this->complete($this->connection->query(
            'SELECT * FROM %n', $this->name,
            'WHERE %n', $this->primary, 'IN (' . $this->primaryModifier, $what, ')'
        ));
    }



    /**
     * Selects all rows.
     * @param  string  column to order by
     * @return DibiResult
     */
    public function findAll($order = NULL)
    {
        if ($order === NULL) {
            return $this->complete($this->connection->query(
                'SELECT * FROM %n', $this->name
            ));
        } else {
            $order = func_get_args();
            return $this->complete($this->connection->query(
                'SELECT * FROM %n', $this->name,
                'ORDER BY %n', $order
            ));
        }
    }



    /**
     * Fetches single row.
     * @param  scalar  primary key value
     * @return array|object row
     */
    public function fetch($what)
    {
        return $this->complete($this->connection->query(
            'SELECT * FROM %n', $this->name,
            'WHERE %n', $this->primary, '=' . $this->primaryModifier, $what
        ))->fetch();
    }



    /**
     * Returns a blank row (not fetched from database).
     * @return array|object
     */
    public function createBlank()
    {
        $row = $this->blankRow;
        $row[$this->primary] = NULL;
        if ($this->connection->getConfig('result:objects')) {
            $row = (object) $row;
        }
        return $row;
    }



    /**
     * User data pre-processing.
     * @param  array|object
     * @return array
     */
    protected function prepare($data)
    {
        if (is_object($data)) {
            return (array) $data;
        } elseif (is_array($data)) {
            return $data;
        }

        throw new InvalidArgumentException('Dataset must be array or anonymous object.');
    }



    /**
     * User DibiResult post-processing.
     * @param  DibiResult
     * @return DibiResult
     */
    protected function complete($res)
    {
        return $res;
    }

}
