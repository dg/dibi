<pre>
<?php

require_once '../dibi/dibi.php';


// required since PHP 5.1.0
if (function_exists('date_default_timezone_set'))
     date_default_timezone_set('Europe/Prague'); // or 'GMT'


// enable log to this file
dibi::$logFile = 'log.sql';

// append mode
dibi::$logMode = 'a';

// log all queries
dibi::$logAll = TRUE;


// mysql
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'database' => 'xxx',
    'charset'  => 'utf8',
));



$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] = %i', 38);


$res = dibi::query('SELECT * FROM [nucleus_item] WHERE [inumber] < %i', 38);


$res = dibi::query('SELECT * FROM [*nucleus_item] WHERE [inumber] < %i', 38);

echo 'See file ', dibi::$logFile;

?>