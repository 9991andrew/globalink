<?php

/**
 * Exception type for invalid map tile specified
 */
class InvalidMapTileException extends Exception {
    /**
     * InvalidMapTileException constructor.
     * @param null $mapId
     * @param null $x
     * @param null $y
     * @param Exception|null $previous
     */
    public function __construct($mapId=null, $x=null, $y=null, Exception $previous = null) {
        if (is_int($mapId) && is_int($x) && is_int($y)) {
            parent::__construct("No MapTile found for id $mapId at $x,$y", 1, $previous);
        } else
            parent::__construct("Invalid arguments passed to MapTile constructor. Integer map_id, x, and y are required.", 1, $previous);
    }

    /**
     * String representation of Exception
     * @return string
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}