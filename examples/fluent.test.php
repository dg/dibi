<h1>dibi dump example</h1>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


$id = 10;
$record = array(
	'title'  => 'Drtička na trávu',
	'price'  => 318,
	'active' => TRUE,
);

// SELECT ...
dibi::select('product_id')->as('id')
	->select('title')
	->from('products')
	->innerJoin('orders')->using('(product_id)')
	->innerJoin('customers USING (customer_id)')
	->orderBy('title')
	->test();

echo "\n";

// INSERT ...
dibi::insert('products', $record)
	->setFlag('IGNORE')
	->test();

echo "\n";

// UPDATE ...
dibi::update('products', $record)
	->where('product_id = %d', $id)
	->test();

echo "\n";

// DELETE ...
dibi::delete('products')
	->where('product_id = %d', $id)
	->test();

echo "\n";

// custom commands
dibi::command()
	->update('products')
	->where('product_id = %d', $id)
	->set($record)
	->test();

echo "\n";

dibi::command()
	->truncate('products')
	->test();
