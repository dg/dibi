<h1>dibi fluent example</h1>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


$id = 10;
$record = array(
	'title'  => 'Super product',
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
// -> SELECT [product_id] AS [id] , [title] FROM [products] INNER JOIN [orders]
//    USING (product_id) INNER JOIN customers USING (customer_id) ORDER BY [title]



echo "\n";

// SELECT ...
echo dibi::select('title')->as('id')
	->from('products')
	->fetchSingle();
// -> Chair (as result of query: SELECT [title] AS [id] FROM [products])



echo "\n";

// INSERT ...
dibi::insert('products', $record)
	->setFlag('IGNORE')
	->test();
// -> INSERT IGNORE INTO [products] ([title], [price], [active]) VALUES ('Super product', 318, 1)



echo "\n";

// UPDATE ...
dibi::update('products', $record)
	->where('product_id = %d', $id)
	->test();
// -> UPDATE [products] SET [title]='Super product', [price]=318, [active]=1 WHERE product_id = 10



echo "\n";

// DELETE ...
dibi::delete('products')
	->where('product_id = %d', $id)
	->test();
// -> DELETE FROM [products] WHERE product_id = 10



echo "\n";

// custom commands
dibi::command()
	->update('products')
	->where('product_id = %d', $id)
	->set($record)
	->test();
// -> UPDATE [products] SET [title]='Super product', [price]=318, [active]=1 WHERE product_id = 10



echo "\n";

dibi::command()
	->truncate('products')
	->test();
// -> TRUNCATE [products]
