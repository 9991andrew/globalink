<?php

/**
 * Exception type for empty bag slot
 */
class EmptyBagSlotException extends Exception {
    /**
     * EmptyBagSlotException constructor.
     * @param string $slotId
     * @param Exception|null $previous
     */
    public function __construct(string $slotId, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct("Slot ID '$slotId' is empty.", 1, $previous);
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}