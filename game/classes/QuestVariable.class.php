<?php
/**
 * Quests may have these QuestVariables with a specified range.
 * Contains a method to generate a random value within this range.
 */

class QuestVariable extends Dbh {
    protected $id;
    // Name user logs in with
    protected $questId;
    protected $name;
    protected $min; // minimum value
    protected $max; // maximum value
    protected $allowDecimal; // Not used yet, could act as a flag to allow decimal values.

    /**
     * Constructor. Needs a an array of values (from pre-existing query)
     * @param any $arg
     * @throws Exception
     */
    public function __construct($arg)
    {
        if (!is_array($arg)) {
            // if $arg isn't an array the constructor wasn't passed valid args
            throw new Exception('Invalid argument passed to QuestVariable constructor.');
        }
        $this->id = (int)$arg['id'];
        $this->questId = (int)$arg['quest_id'];
        $this->name = $arg['var_name'];
        $this->min = (int)$arg['min_value'];
        $this->max = (int)$arg['max_value'];
        $this->allowDecimal = $arg['allow_decimal']==1;
    }

    public function getId() {return $this->id;}
    public function getQuestId() {return $this->questId;}
    public function getName() {return $this->name;}

    /**
     * Returns a random value in the range specified for this variable.
     * @return int
     */
    public function getRandomValue(): int
    {
        return rand($this->min, $this->max);
    }

}// end QuestVariable class
