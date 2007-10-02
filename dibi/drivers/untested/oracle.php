<?php

/**
 * This file is part of the "dibi" project (http://dibi.texy.info/)
 *
 * @author     David Grudl
 * @copyright  Copyright (c) 2005-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 * @license    New BSD License
 * @version    $Revision: 65 $ $Date: 2007-10-01 07:34:50 +0200 (po, 01 X 2007) $
 * @category   Database
 * @package    Dibi
 */


/**
 * The dibi driver for Oracle database
 *
 */
class DibiOracleDriver extends DibiDriver
{
    public $formats = array(
        'TRUE'     => "1",
        'FALSE'    => "0",
        'date'     => "U",
        'datetime' => "U",
    );

    private $autocommit = TRUE;



    /**
     * @param array  connect configuration
     * @throws DibiException
     */
    public function __construct($config)
    {
        if (empty($config['username'])) {
            throw new DibiException("Username must be specified");
        }

        if (empty($config['password'])) {
            throw new DibiException("Password must be specified");
        }

        if (!isset($config['db'])) $config['db'] = NULL;
        if (!isset($config['charset'])) $config['charset'] = NULL;

        parent::__construct($config);
    }



    protected function connect()
    {
        if (!extension_loaded('oci8')) {
            throw new DibiException("PHP extension 'oci8' is not loaded");
        }

        $config = $this->getConfig();
        $connection = @oci_new_connect($config['username'], $config['password'], $config['db'], $config['charset']);

        if (!$connection) {
            $err = oci_error();
            throw new DibiDatabaseException($err['message'], $err['code']);
        }

        dibi::notify('connected', $this);
        return $connection;
    }



    protected function doQuery($sql)
    {
        $connection = $this->getConnection();

        $statement = oci_parse($connection, $sql);
        if ($statement) {
            $res = oci_execute($statement, $this->autocommit ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT);
            if (!$res) {
                $err = oci_error($statement);
                throw new DibiDatabaseException($err['message'], $err['code'], $sql);
            }
        } else {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code'], $sql);
        }

        // TODO!
        return is_resource($res) ? new DibiOracleResult($statement) : TRUE;
    }



    public function affectedRows()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    public function insertId()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    public function begin()
    {
        $this->autocommit = FALSE;
    }



    public function commit()
    {
        $connection = $this->getConnection();
        if (!oci_commit($connection)) {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
        dibi::notify('commit', $this);
    }



    public function rollback()
    {
        $connection = $this->getConnection();
        if (!oci_rollback($connection)) {
            $err = oci_error($connection);
            throw new DibiDatabaseException($err['message'], $err['code']);
        }
        $this->autocommit = TRUE;
        dibi::notify('rollback', $this);
    }



    public function errorInfo()
    {
        return oci_error($this->getConnection());
    }



    public function escape($value, $appendQuotes = TRUE)
    {
        return $appendQuotes
               ? "'" . sqlite_escape_string($value) . "'"
               : sqlite_escape_string($value);
    }



    public function delimite($value)
    {
        return '[' . str_replace('.', '].[', $value) . ']';
    }



    public function getMetaData()
    {
        throw new DibiException(__METHOD__ . ' is not implemented');
    }



    /**
     * @see DibiDriver::applyLimit()
     */
    public function applyLimit(&$sql, $limit, $offset = 0)
    {
        if ($limit < 0 && $offset < 1) return;
        $sql .= ' LIMIT ' . $limit . ($offset > 0 ? ' OFFSET ' . (int) $offset : '');
    }

} // class DibiOracleDriver









class DibiOracleResult extends DibiResult
{

    public function rowCount()
    {
        return oci_num_rows($this->resource);
    }



    protected function doFetch()
    {
        return oci_fetch_assoc($this->resource);
    }



    public function seek($row)
    {
        //throw new DibiException(__METHOD__ . ' is not implemented');
    }



    protected function free()
    {
        oci_free_statement($this->resource);
    }



    /** this is experimental */
    protected function buildMeta()
    {
        $count = oci_num_fields($this->resource);
        $this->meta = $this->convert = array();
        for ($index = 0; $index < $count; $index++) {
            $name = oci_field_name($this->resource, $index + 1);
            $this->meta[$name] = array('type' => dibi::FIELD_UNKNOWN);
            $this->convert[$name] = dibi::FIELD_UNKNOWN;
        }
    }


} // class DibiOracleResult
