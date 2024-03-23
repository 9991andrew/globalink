<?php

/**
 * Exception type for invalid quest specified
 */
class InvalidQuestException extends Exception {
    /**
     * InvalidQuestException constructor.
     * @param null $questId
     * @param Exception|null $previous
     */
    public function __construct($questId=null, Exception $previous = null) {
        // make sure everything is assigned properly
        if (is_int($questId)) {
            parent::__construct("Quest with id '$questId' does not exist.", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Quest constructor.", 1, $previous);
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