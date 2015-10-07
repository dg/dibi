<?php

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$conn = new DibiConnection($config);

class DibiFluentMock extends DibiFluent
{
    public function export($clause = null, array $args = array())
    {
        return $this->_export($clause, $args);
    }
}

$fluent = new DibiFluentMock($conn);
$fluent->select('name')->from('customers');

Assert::equal(array('SELECT', '%n', 'name', 'FROM', '%n', 'customers'), $fluent->export());
Assert::same(array(), $fluent->export('limit'));
Assert::same(array(), $fluent->export('LIMIT'));
Assert::equal(array('SELECT', '%n', 'name'), $fluent->export('select'));
