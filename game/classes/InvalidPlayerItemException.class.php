<?php

/**
 * Exception type for invalid player item specified
 */
class InvalidPlayerItemException extends Exception {
    /**
     * InvalidPlayerItemException constructor.
     * @param string $itemGUID
     * @param Exception|null $previous
     */
    public function __construct(string $itemGUID, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct("Player item with guid '$itemGUID' does not exist.", 1, $previous);
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}