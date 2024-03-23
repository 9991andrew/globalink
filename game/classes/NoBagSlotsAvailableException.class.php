<?php

/**
 * Exception type thrown when an inventory slot is needed but none are available
 */
class NoBagSlotsAvailableException extends Exception {
    /**
     * NoBagSlotsAvailableException constructor.
     * @param Exception|null $previous
     */
    public function __construct(Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct(_("No bag slots are available to store this item."), 1, $previous);
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}