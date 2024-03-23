<?php

/**
 * Exception type for invalid profession specified
 */
class InvalidProfessionException extends Exception {
    /**
     * InvalidProfessionException constructor.
     * @param null $professionId
     * @param Exception|null $previous
     */
    public function __construct($professionId=null, Exception $previous = null) {
        if (is_int($professionId)) {
            // make sure everything is assigned properly
            parent::__construct("Profession '$professionId' does not exist.", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Profession constructor.", 1, $previous);
        }
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}