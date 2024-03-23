<?php

/**
 * Exception type for invalid map specified
 */
class InvalidMapException extends Exception {
    /**
     * InvalidMapException constructor.
     * @param $mapId
     * @param Exception|null $previous
     */
    public function __construct($mapId, Exception $previous = null) {
        parent::__construct("No Map found for map_id $mapId", 1, $previous);
    }

    /**
     * String representation of Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}