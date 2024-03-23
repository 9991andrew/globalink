<?php

/**
 * Exception type for invalid item specified
 */
class InvalidItemException extends Exception {
    /**
     * InvalidItemException constructor.
     * @param string|null $itemId
     * @param Exception|null $previous
     */
    public function __construct(string $itemId=null, Exception $previous = null) {
        // make sure everything is assigned properly
        if (is_int($itemId)) {
            parent::__construct("Item with id '$itemId' does not exist.", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Item constructor. Integer item_id is required.", 1, $previous);
        }
    }

    /**
     * String representation of Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}