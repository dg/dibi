<?php

require_once '../dibi/dibi.php';


// connects to mysqli
dibi::connect(array(
    'driver'   => 'mysqli',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',  // change to real password!
    'charset'  => 'utf8',
));


// create new substitution :blog:  ==>  wp_items_
dibi::addSubst('blog', 'wp_items_');


// generate and dump SQL
dibi::test("UPDATE [:blog:items] SET [text]='Hello World'");


// create new substitution :: (empty)  ==>  my_
dibi::addSubst('', 'my_');


// generate and dump SQL
dibi::test("UPDATE [database.::table] SET [text]='Hello World'");


?>