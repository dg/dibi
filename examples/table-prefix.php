<h1>dibi prefix & substitute example</h1>
<?php

require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));


// create new substitution :blog:  ==>  wp_
dibi::addSubst('blog', 'wp_');


// generate and dump SQL
dibi::test("UPDATE [:blog:items] SET [text]='Hello World'");




// create new substitution :: (empty)  ==>  my_
dibi::addSubst('', 'my_');


// generate and dump SQL
dibi::test("UPDATE [database.::table] SET [text]='Hello World'");





function substFallBack($expr)
{
	return 'the_' . $expr;
}

// create substitution fallback
dibi::setSubstFallBack('substFallBack');


// generate and dump SQL
dibi::test("UPDATE [:account:user] SET [name]='John Doe'");
