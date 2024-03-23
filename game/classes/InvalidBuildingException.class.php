<?php

/**
 * Exception type for invalid building specified
 */
class InvalidBuildingException extends Exception {
    /**
     * InvalidBuildingException constructor.
     * @param null $arg
     * @param null $x
     * @param null $y
     * @param Exception|null $previous
     */
    public function __construct($arg=null, $x=null, $y=null, Exception $previous = null) {
        if (is_int($arg) && is_int($x) && is_int($y)) {
            parent::__construct("No Building found on map_id $arg at $x,$y", 1, $previous);
        } else if (is_int($arg)) {
            parent::__construct("No Building found with building_id $arg", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Building constructor.", 1, $previous);
        }
    }

    /**
     * Custom string representation of Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}