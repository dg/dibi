<h1>DibiTable demo</h1>
<pre>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));

dibi::begin();


// autodetection: primary keys are customer_id, order_id, ...
DibiTable::$primaryMask = '%s_id';


// table products
class Products extends DibiTable
{
//   rely on autodetection...
//   protected $name = 'products';
//   protected $primary = 'product_id';

}





// create table object
$products = new Products();

echo "Table name: $products->name\n";
echo "Primary key: $products->primary\n";


// Finds rows by primary key
foreach ($products->find(1, 3) as $row) {
	print_r($row);
}


// select all
$products->findAll()->dump();


// select all, order by title, product_id
$products->findAll('title', $products->primary)->dump();
$products->findAll(array('title' => 'Chair'), 'title')->dump();


// fetches single row with id 3
$row = $products->fetch(3);


// deletes row from a table
$count = $products->delete(1);

// deletes multiple rows
$count = $products->delete(array(1, 2, 3));
var_dump($count); // number of deleted rows


// update row #2 in a table
$data = (object) NULL;
$data->title = 'New title';
$count = $products->update(2, $data);
var_dump($count); // number of updated rows


// update multiple rows in a table
$count = $products->update(array(3, 5), $data);
var_dump($count); // number of updated rows


// inserts row into a table
$data = array();
$data['title'] = 'New product';
$id = $products->insert($data);
var_dump($id); // generated id


// is absolutely SQL injection safe
$key = '3 OR 1=1';
$products->delete($key);
// --> DELETE FROM  [products] WHERE  [product_id] IN ( 3 )


// select all using fluent interface
var_dump($products->select('*')->orderBy('title')->fetchAll());
