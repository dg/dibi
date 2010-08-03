<!DOCTYPE html><link rel="stylesheet" href="data/style.css">

<h1>Using Substitutions | dibi</h1>

<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'data/sample.sdb',
));




// create new substitution :blog:  ==>  wp_
dibi::addSubst('blog', 'wp_');

dibi::test("SELECT * FROM [:blog:items]");
// -> SELECT * FROM [wp_items]





// create substitutions using fallback callback
function substFallBack($expr)
{
	$const = 'SUBST_' . strtoupper($expr);
	if (defined($const)) {
		return constant($const);
	} else {
		throw new Exception("Undefined substitution :$expr:");
	}
}

// define callback
dibi::setSubstFallBack('substFallBack');

// define substitutes as constants
define('SUBST_ACCOUNT', 'eshop_');
define('SUBST_ACTIVE', 7);

dibi::test("
	UPDATE [:account:user]
	SET [name]='John Doe', [status]=:active:
	WHERE id=", 7
);
// -> UPDATE [the_accountuser] SET [name]='John Doe', [status]=7 WHERE id=7
