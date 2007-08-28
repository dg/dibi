<pre>
<?php

require_once '../dibi/dibi.php';


// connects to SQlite
try {
    dibi::connect(array(
        'driver'   => 'sqlite',
        'database' => 'sample.sdb',
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}



// connects to MySQL using DSN
try {
    dibi::connect('driver=mysql&host=localhost&username=root&password=xxx&database=test&charset=utf8');

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}




// connects to MySQL / MySQLi
try {
    dibi::connect(array(
        'driver'   => 'mysql',  // or 'mysqli'
        'host'     => 'localhost',
        'username' => 'root',
        'password' => 'xxx',
        'database' => 'dibi',
        'charset'  => 'utf8',
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}




// connects to ODBC
try {
    dibi::connect(array(
        'driver'   => 'odbc',
        'username' => 'root',
        'password' => '***',
        'database' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq=C:\\Database.mdb',
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}




// connects to PostgreSql
try {
    dibi::connect(array(
        'driver'     => 'postgre',
        'string'     => 'host=localhost port=5432 dbname=mary',
        'persistent' => TRUE,
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}




// connects to PDO
try {
    dibi::connect(array(
        'driver'  => 'pdo',
        'dsn'     => 'sqlite2::memory:',
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}



// connects to MS SQL
try {
    dibi::connect(array(
        'driver'   => 'mssql',
        'host'     => 'localhost',
        'username' => 'root',
        'password' => 'xxx',
    ));

} catch (DibiException $e) {
    echo 'DibiException: ', $e;
}
