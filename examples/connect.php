<?php

require_once '../dibi/dibi.php';



// connects to mysql
$state = dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'database' => 'test',
    'charset'  => 'utf8',
));

/* connects to ODBC
dibi::connect(array(
    'driver'   => 'odbc',
    'username' => 'root',
    'password' => '***',
    'database' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq=C:\\Database.mdb',
));
*/


// check status
if (!dibi::isConnected()) {
    echo 'dibi::isConnected(): Not connected';
    echo "<br>\n";
}


// or checked status this way
if (is_error($state)) {

    // $state can be FALSE or Exception
    if ($state instanceof Exception) 
        echo $state;
    else 
        echo 'FALSE';

    echo "<br>\n";
}



?>