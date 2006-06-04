<pre>
<?php

require_once '../dibi/dibi.php';


dibi::$logfile = 'log.sql';


// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '***',
    'database' => 'test',
    'charset'  => 'utf8',
));



$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] = %i', 38);


$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] < %i', 38);


$res = dibi::query('SELECT * FROM [*nucleus_item] WHERE [inumber] < %i', 38);


?>