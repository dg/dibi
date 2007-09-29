<h1>dibi connect example</h1>
<?php

require_once '../dibi/dibi.php';


// connects to SQlite
try {
    dibi::connect(array(
        'driver'   => 'sqlite',
        'database' => 'sample.sdb',
    ));
    echo '<p>Connected to Sqlite</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}



// connects to MySQL using DSN
try {
    dibi::connect('driver=mysql&host=localhost&username=root&password=xxx&database=test&charset=utf8');
    echo '<p>Connected to MySQL</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
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
    echo '<p>Connected to MySQL</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}




// connects to ODBC
try {
    dibi::connect(array(
        'driver'   => 'odbc',
        'username' => 'root',
        'password' => '***',
        'database' => 'Driver={Microsoft Access Driver (*.mdb)};Dbq='.dirname(__FILE__).'/sample.mdb',
    ));
    echo '<p>Connected to ODBC</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}




// connects to PostgreSql
try {
    dibi::connect(array(
        'driver'     => 'postgre',
        'string'     => 'host=localhost port=5432 dbname=mary',
        'persistent' => TRUE,
    ));
    echo '<p>Connected to PostgreSql</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}




// connects to PDO
try {
    dibi::connect(array(
        'driver'  => 'pdo',
        'dsn'     => 'sqlite2::memory:',
    ));
    echo '<p>Connected to Sqlite via PDO</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}



// connects to MS SQL
try {
    dibi::connect(array(
        'driver'   => 'mssql',
        'host'     => 'localhost',
        'username' => 'root',
        'password' => 'xxx',
    ));
    echo '<p>Connected to MS SQL</p>';

} catch (DibiException $e) {
    echo '<pre>', $e, '</pre>';
}
