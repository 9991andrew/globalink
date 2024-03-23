<?php

/**
 * Exception type for invalid NPC portal specified
 */
class InvalidNPCPortalException extends Exception {
    /**
     * InvalidNPCPortalException constructor.
     * @param null $arg The argument passed to the constructor of NPCPortal
     * @param Exception|null $previous
     */
    public function __construct($arg=null, Exception $previous = null) {
        if (is_int($arg)) {
            parent::__construct("No NPC Portal found with npc_portal_id $arg", 1, $previous);
        } else {
            parent::__construct("Invalid argument passed to NpcPortal constructor.", 1, $previous);
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