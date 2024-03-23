<?php

/**
 * Exception type for invalid player specified
 */
class InvalidPlayerException extends Exception {
    /**
     * InvalidPlayerException constructor.
     * @param int|null $playerId
     * @param Exception|null $previous
     */
    public function __construct(int $playerId=null, Exception $previous = null) {
        // make sure everything is assigned properly
        if (is_int($playerId)) {
            parent::__construct("Player with id '$playerId' does not exist.", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Player constructor. Integer or Array required.", 1, $previous);
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