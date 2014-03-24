<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 */

require __DIR__ . '/../../vendor/nette/tester/Tester/bootstrap.php';
require __DIR__ . '/../../dibi/dibi.php';
$config = require __DIR__ . '/config.php';

date_default_timezone_set('Europe/Prague');
