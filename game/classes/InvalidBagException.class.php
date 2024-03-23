<?php

/**
 * Exception type for invalid bag specified
 */
class InvalidBagException extends Exception {
    /**
     * InvalidBagException constructor.
     * @param string $bagId
     * @param Exception|null $previous
     */
    public function __construct(string $bagId, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct("Bag '$bagId' does not exist.", 1, $previous);
    }


    /**
     * String representation of Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}