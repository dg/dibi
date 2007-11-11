<h1>dibi logger example</h1>
<?php

require_once '../dibi/dibi.php';

// enable log to this file, TRUE means "log all queries"
dibi::startLogger('log.sql', TRUE);



dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));



try {
    $res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] = %i', 1);

    $res = dibi::query('SELECT * FROM [customers] WHERE [customer_id] < %i', 5);

    $res = dibi::query('SELECT FROM [customers] WHERE [customer_id] < %i', 38);

} catch (DibiException $e) {
    echo '<p>', get_class($e), ': ', $e->getMessage(), '</p>';
}


echo "<h2>File log.sql:</h2>";

echo '<pre>', file_get_contents('log.sql'), '</pre>';
