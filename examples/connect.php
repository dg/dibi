<?php

require_once '../dibi/dibi.php';

// use two connections:

// first connection to mysql
$state = dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => '***',
    'database' => 'test',
    'charset'  => 'utf8',
), 1);

if ($state instanceof Exception) {
    echo $state;
}

if (!dibi::isConnected()) {
    die();
}



// second connection to odbc
dibi::connect(array(
    'driver'   => 'odbc',
    'username' => 'root',
    'password' => '***',
    'database' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq=C:\\Database.mdb',
), 3);


echo dibi::isConnected();



?>