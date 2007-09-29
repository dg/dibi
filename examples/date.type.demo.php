<h1>dibi user datatype example</h1>
<?php

require_once '../dibi/dibi.php';


// required since PHP 5.1.0
if (function_exists('date_default_timezone_set')) {
     date_default_timezone_set('Europe/Prague');
}


/**
 * Pseudotype for UNIX timestamp representation
 */
class MyDateTime implements DibiVariableInterface
{
    /**
     * Unix timestamp
     * @var int
     */
    protected $time;



    public function __construct($time = NULL)
    {
        if ($time === NULL)
            $this->time = time(); // current time

        elseif (is_string($time))
            $this->time = strtotime($time); // try convert to timestamp

        else
            $this->time = (int) $time;
    }



    /**
     * Format for SQL
     *
     * @param  object  destination DibiDriver
     * @param  string  optional modifier
     * @return string
     */
    public function toSQL($driver, $modifier = NULL)
    {
        return date(
            $driver->formats['datetime'],  // format according to driver's spec.
            $this->time
        );
    }



}



// CHANGE TO REAL PARAMETERS!
dibi::connect(array(
    'driver'   => 'mysql',
    'host'     => 'localhost',
    'username' => 'root',
    'password' => 'xxx',
    'database' => 'dibi',
    'charset'  => 'utf8',
));



// generate and dump SQL
dibi::test("
INSERT INTO [mytable]", array(
    'A' => 12,
    'B' => NULL,
    'C' => new MyDateTime(31542),  // using out class
    'D' => 'any string',
));
