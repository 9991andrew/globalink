<?php

/**
 * A bag (number of slots). Currently each player has a main bag and that is what this class represents.
 * Future expansions of MEGA World may allow this to be used for other bags that could be found by players or stored.
 */
class Bag extends Dbh {
    protected $id;
    // name of the bag
    protected $name;
    // bag description
    protected $description;
    // List of the slotIds for this bag
    protected $slots;


    /**
     *  Main constructor
     * @param int $id - Id of the bag from the bags table
     * @throws InvalidBagException
     */
    public function __construct(int $id=1)
    {
        $this->id=$id;

        // Check that npc exists and throw exception if not.
        $sql = "SELECT * FROM bags WHERE id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$id]);
        $count = $stmt->rowCount();

        if ($count == 0) throw new InvalidBagException($id);
        $result = $stmt->fetch();
        $this->name = $result['name'];
        $this->description = $result['description'];

        $this->slots = Array();
        // Get the array of slots

        // Populate the array of slotIds
        $sql = "SELECT * FROM bag_slots WHERE bag_id = ? ORDER BY slot_seq;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$id]);
        $count = $stmt->rowCount();

        // We can create items with no DB query by passing the whole row to the PlayerItem constructor
        while ($row = $stmt->fetch())
        {
            array_push($this->slots, (int)$row['id']);
        }
    }

    /**
    * Simply return the number of slots in the slots array
    */
    public function getSlotCount() {
        return sizeof($this->slots);
    }

    public function getName() {return $this->name;}
    public function getDescription() {return $this->description;}
    public function getSlots() {return $this->slots;}

}