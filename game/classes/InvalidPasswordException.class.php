<?php

/**
 * Exception type for incorrect password.
 */
class InvalidPasswordException extends Exception {
    /**
     * InvalidPasswordException constructor.
     * @param Exception|null $previous
     */
    public function __construct(Exception $previous = null) {
        // make sure everything is assigned properly
        parent::__construct(_("Invalid password."), 2, $previous);
    }

    /**
     * String representation of Exception.
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}