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



dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));



// generate user-level errors
dibi::$throwExceptions = FALSE;
echo '<h1>User-level errors</h1>';


$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] = %i', 1);


$res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] < %i', 5);


$res = dibi::query('SELECT FROM [customers] WHERE [customer_id] < %i', 5);

echo "<br />See file ", dibi::$logFile;



// generate DibiException
dibi::$throwExceptions = TRUE;
echo '<h1>DibiException</h1>';

try {

    $res = dibi::query('SELECT FROM [customers] WHERE [customer_id] < %i', 38);

} catch (DibiException $e) {

    echo '<pre>', $e, '</pre>';

    echo '<h2>$e->getSql()</h2>';
    $sql = $e->getSql();
    echo "SQL: $sql\n";

    echo '<h2>$e->getDbError()</h2>';
    $error = $e->getDbError();
    echo '<pre>';
    print_r($error);
    echo '</pre>';

}