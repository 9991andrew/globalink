<?php

/**
 * Exception type for invalid user specified.
 */
class InvalidUserException extends Exception {
    /**
     * InvalidUserException constructor.
     * @param string $userName
     * @param Exception|null $previous
     */
    public function __construct(string $userName, Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct(sprintf(_('User "%s" does not exist.'), $userName), 1, $previous);
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}