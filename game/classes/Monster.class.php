<?php

class Monster extends Dbh {

    protected $id;
    protected $name;
    protected $hp;
    protected $item_id;
    protected $drop_rate;
    protected $map_id;

    public $x;
    public $y;

    
    public function __construct($id = null) {
        if ($id !== null) {
            $sql = "SELECT id, name, hp, item_id, drop_rate, map_id FROM monster WHERE id = ?";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch();

            if ($result) {
                $this->id = (int) $result['id'];
                $this->name = $result['name'];
                $this->hp = (int) $result['hp'];
                $this->item_id = (int) $result['item_id'];
                $this->drop_rate = (int) $result['drop_rate'];
                $this->map_id = (int) $result['map_id'];
            
            }
        }
       
    }



    public function getId(): int {
        return $this->id;
    }
    public function getName(): string { 
        return $this->name;
    }
    public function getHp() {
        return $this->hp;
    }
    public function getItemId(): int {  
        return $this->item_id;
    }
    public function getDropRate(): int {
        return $this->drop_rate;
    }
    public function getMapId(): int {
        return $this->map_id;
    }
    public function getX() {
        return $this->x;
    }
    public function getY() {
        return $this->y;
    }
    public function setCoordinate($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
    public static function fetchAllMonstersForMap($mapId) {
        $db = new self();
        $query = "SELECT * FROM monster WHERE map_id=?";
        $stmt = $db->connect()->prepare($query);
        $stmt->execute([$mapId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $monsters = [];
        foreach($results as $row) {
            $monsters[] = new self($row['id']);
        }
        return $monsters;
    }
    public static function spawnMonsters($monsters, $probability, $tiles, $maxSpawnCount) {
        if (!is_array($monsters) || empty($monsters)) {
            return []; // Ensure we have a valid, non-empty array of monsters
        }
    
        $spawnedMonsters = [];
    
        // Iterate over the tiles to potentially spawn monsters
        foreach ($tiles as $y => $row) {
            foreach ($row as $x => $tile) {
                // Check if we've reached the maximum number of spawns allowed
                if (count($spawnedMonsters) >= $maxSpawnCount) {
                    break 2; // Exit both loops
                }
    
                // Use probability to determine if a monster should spawn on this tile
                if (rand(1, 100) <= $probability * 0.5) {
                    $index = array_rand($monsters); // Select a random monster from the array
                    $monster = clone $monsters[$index]; // Clone the monster to avoid modifying the original
                    $monster->setCoordinate($x, $y); // Set the monster's coordinates
                    $spawnedMonsters[] = $monster;
                }
            }
        }
    
        return $spawnedMonsters;
    }
    
    
    

}