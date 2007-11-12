<h1>DibiVariableInterface example</h1>
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
    public function toSql(DibiDriverInterface $driver, $modifier)
    {
        return $driver->format($this->time, dibi::FIELD_DATETIME);  // format according to driver's spec.
    }



}



// CHANGE TO REAL PARAMETERS!
dibi::connect(array(
    'driver'   => 'sqlite',
    'database' => 'sample.sdb',
));



// generate and dump SQL
dibi::test("
INSERT INTO [mytable]", array(
    'A' => 12,
    'B' => NULL,
    'C' => new MyDateTime(31542),  // using out class
    'D' => 'any string',
));
