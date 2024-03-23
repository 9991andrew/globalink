<?php

/**
 * Exception type for invalid NPC specified
 */
class InvalidNpcException extends Exception {
    /**
     * InvalidNpcException constructor.
     * @param null $npcId
     * @param Exception|null $previous
     */
    public function __construct($npcId=null, Exception $previous = null) {
        if (is_int($npcId)) {
            // make sure everything is assigned properly
            parent::__construct("NPC '$npcId' does not exist.", 1, $previous);
        } else {
            parent::__construct("Invalid arguments passed to Npc constructor.", 1, $previous);
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