<?php

require_once '../dibi/dibi.php';


// CHANGE TO REAL PARAMETERS!
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',
    'database' => 'dibi',
    'charset'  => 'utf8',
));


// create new substitution :blog:  ==>  wp_
dibi::addSubst('blog', 'wp_');


// generate and dump SQL
dibi::test("UPDATE [:blog:items] SET [text]='Hello World'");


// create new substitution :: (empty)  ==>  my_
dibi::addSubst('', 'my_');


// generate and dump SQL
dibi::test("UPDATE [database.::table] SET [text]='Hello World'");


