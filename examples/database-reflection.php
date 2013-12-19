<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Database Reflection | dibi</h1>

<?php

require __DIR__ . '/../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite3',
	'database' => 'data/sample.s3db',
));


// retrieve database reflection
$database = dibi::getDatabaseInfo();

echo "<h2>Database '{$database->name}'</h2>\n";
echo "<ul>\n";
foreach ($database->getTables() as $table) {
	echo '<li>', ($table->view ? 'view' : 'table') . " $table->name</li>\n";
}
echo "</ul>\n";


// table reflection
$table = $database->getTable('products');

echo "<h2>Table '{$table->name}'</h2>\n";

echo "Columns\n";
echo "<ul>\n";
foreach ($table->getColumns() as $column) {
	echo "<li>{$column->name} <i>{$column->nativeType}</i> <code>{$column->default}</code></li>\n";
}
echo "</ul>\n";


echo "Indexes";
echo "<ul>\n";
foreach ($table->getIndexes() as $index) {
	echo "<li>{$index->name} " . ($index->primary ? 'primary ' : '') . ($index->unique ? 'unique' : '') . ' (';
	foreach ($index->getColumns() as $column) {
		echo "$column->name, ";
	}
	echo ")</li>\n";
}
echo "</ul>\n";
