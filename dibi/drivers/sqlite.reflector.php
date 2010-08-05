<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi\drivers
 */


/**
 * The dibi reflector for SQLite database.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi\drivers
 * @internal
 */
class DibiSqliteReflector extends DibiObject implements IDibiReflector
{
	/** @var IDibiDriver */
	private $driver;



	public function __construct(IDibiDriver $driver)
	{
		$this->driver = $driver;
	}



	/**
	 * Returns list of tables.
	 * @return array
	 */
	public function getTables()
	{
		$this->driver->query("
			SELECT name, type = 'view' as view FROM sqlite_master WHERE type IN ('table', 'view')
			UNION ALL
			SELECT name, type = 'view' as view FROM sqlite_temp_master WHERE type IN ('table', 'view')
			ORDER BY name
		");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$res[] = $row;
		}
		$this->driver->free();
		return $res;
	}



	/**
	 * Returns metadata for all columns in a table.
	 * @param  string
	 * @return array
	 */
	public function getColumns($table)
	{
		$this->driver->query("
			SELECT sql FROM sqlite_master WHERE type = 'table' AND name = '$table'
			UNION ALL
			SELECT sql FROM sqlite_temp_master WHERE type = 'table' AND name = '$table'"
		);
		$meta = $this->driver->fetch(TRUE);
		$this->driver->free();

		$this->driver->query("PRAGMA table_info([$table])");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$column = $row['name'];
			$pattern = "/(\"$column\"|\[$column\]|$column)\s+[^,]+\s+PRIMARY\s+KEY\s+AUTOINCREMENT/Ui";
			$type = explode('(', $row['type']);

			$res[] = array(
				'name' => $column,
				'table' => $table,
				'fullname' => "$table.$column",
				'nativetype' => strtoupper($type[0]),
				'size' => isset($type[1]) ? (int) $type[1] : NULL,
				'nullable' => $row['notnull'] == '0',
				'default' => $row['dflt_value'],
				'autoincrement' => (bool) preg_match($pattern, $meta['sql']),
				'vendor' => $row,
			);
		}
		$this->driver->free();
		return $res;
	}



	/**
	 * Returns metadata for all indexes in a table.
	 * @param  string
	 * @return array
	 */
	public function getIndexes($table)
	{
		$this->driver->query("PRAGMA index_list([$table])");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$res[$row['name']]['name'] = $row['name'];
			$res[$row['name']]['unique'] = (bool) $row['unique'];
		}
		$this->driver->free();

		foreach ($res as $index => $values) {
			$this->driver->query("PRAGMA index_info([$index])");
			while ($row = $this->driver->fetch(TRUE)) {
				$res[$index]['columns'][$row['seqno']] = $row['name'];
			}
		}
		$this->driver->free();

		$columns = $this->getColumns($table);
		foreach ($res as $index => $values) {
			$column = $res[$index]['columns'][0];
			$primary = FALSE;
			foreach ($columns as $info) {
				if ($column == $info['name']) {
					$primary = $info['vendor']['pk'];
					break;
				}
			}
			$res[$index]['primary'] = (bool) $primary;
		}
		if (!$res) { // @see http://www.sqlite.org/lang_createtable.html#rowid
			foreach ($columns as $column) {
				if ($column['vendor']['pk']) {
					$res[] = array(
						'name' => 'ROWID',
						'unique' => TRUE,
						'primary' => TRUE,
						'columns' => array($column['name']),
					);
					break;
				}
			}
		}

		return array_values($res);
	}



	/**
	 * Returns metadata for all foreign keys in a table.
	 * @param  string
	 * @return array
	 */
	public function getForeignKeys($table)
	{
		if (!($this->driver instanceof DibiSqlite3Driver)) {
			// throw new NotSupportedException; // @see http://www.sqlite.org/foreignkeys.html
		}
		$this->driver->query("PRAGMA foreign_key_list([$table])");
		$res = array();
		while ($row = $this->driver->fetch(TRUE)) {
			$res[$row['id']]['name'] = $row['id']; // foreign key name
			$res[$row['id']]['local'][$row['seq']] = $row['from']; // local columns
			$res[$row['id']]['table'] = $row['table']; // referenced table
			$res[$row['id']]['foreign'][$row['seq']] = $row['to']; // referenced columns
			$res[$row['id']]['onDelete'] = $row['on_delete'];
			$res[$row['id']]['onUpdate'] = $row['on_update'];

			if ($res[$row['id']]['foreign'][0] == NULL) {
				$res[$row['id']]['foreign'] = NULL;
			}
		}
		$this->driver->free();
		return array_values($res);
	}

}
