<?php
/**
 * Specifies the range a variable might have and contains a function to generate player item variables.
 */

class ItemVariable extends Dbh {
    protected $id;
    protected $itemId;
    protected $name;
    protected $min; // minimum value
    protected $max; // maximum value
    protected $allowDecimal; // Not used yet, could act as a flag to allow decimal values.

    /**
     * Constructor. Needs a an array of values (from pre-existing query)
     * @param any $arg
     * @throws Exception
     */
    public function __construct($arg)
    {
        if (!is_array($arg)) {
            // if $arg isn't an array the constructor wasn't passed valid args
            throw new Exception('Invalid argument passed to ItemVariable constructor.');
        }
        $this->id = (int)$arg['id'];
        $this->itemId = (int)$arg['item_id'];
        $this->name = $arg['var_name'];
        $this->min = (int)$arg['min_value'];
        $this->max = (int)$arg['max_value'];
        $this->allowDecimal = $arg['allow_decimal']==1;
    }

    public function getId() {return $this->id;}
    public function getItemId() {return $this->itemId;}
    public function getName() {return $this->name;}

    /**
     * Returns a random value in the range specified for this variable.
     * @return int
     */
    public function getRandomValue(): int
    {
        return rand($this->min, $this->max);
    }

}// end ItemVariable class
