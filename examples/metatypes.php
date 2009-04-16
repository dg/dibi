<h1>dibi metatypes example</h1>
<pre>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';

date_default_timezone_set('Europe/Prague');


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


$res = dibi::query('SELECT * FROM [customers]');

// auto-converts this column to integer
$res->setType('customer_id', Dibi::INTEGER);
$res->setType('added', Dibi::DATETIME, 'H:i j.n.Y');

$row = $res->fetch();
Debug::dump($row);
// outputs:
// object(DibiRow)#3 (3) {
//     customer_id => int(1)
//     name =>  string(11) "Dave Lister"
//     added =>  string(15) "17:20 11.3.2007"
// }
