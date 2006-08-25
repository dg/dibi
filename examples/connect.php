<?php

require_once '../dibi/dibi.php';

// using DSN
$state = dibi::connect('driver=mysql&host=localhost&username=root&password=xxx&database=test&charset=utf8');


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
} else {
    echo 'Connected';
}

?>