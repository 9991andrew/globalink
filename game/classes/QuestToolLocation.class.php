<?php
/**
 * Specifies the locations at which a tool can be used to find items or complete quests.
 */

class QuestToolLocation extends Dbh {
    protected $id;
    // Name user logs in with
    protected $questToolId;
    // The item that will be found and received by the player
    protected $itemId;
    // The quantity of the item to be received by the player
    protected $itemAmount;
    protected $mapId;
    // x and y are not integers but equations to be evaluated with MathEval
    protected $x;
    protected $y;
    // If the question uses a true/false or multiple choice, the correct value will be boolean
    protected $successMessage;
    // if true, this will cause the quest to be completed successfully when the tool is used at this location.
    protected $questComplete;

    /**
     * Constructor. Needs a an array of values (from pre-existing query)
     * @param any $arg
     * @throws Exception
     */
    public function __construct($arg)
    {
        if (!is_array($arg)) {
            // if $arg isn't an array the constructor wasn't passed valid args
            throw new Exception('Invalid argument passed to QuestToolLocation constructor.');
        }
        $this->id = (int)$arg['id'];
        $this->questToolId = (int)$arg['quest_tool_id'];
        $this->itemId = (int)$arg['item_id'];
        $this->itemAmount = (int)$arg['item_amount'];
        $this->mapId = (int)$arg['map_id'];
        $this->x = $arg['x'];
        $this->y = $arg['y'];
        $this->successMessage = $arg['success_message'];
        // Boolean
        $this->questComplete = 1 == $arg['quest_complete'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getQuestToolId(): int
    {
        return $this->questToolId;
    }

    /**
     * @return int
     */
    public function getItemId(): int
    {
        return $this->itemId;
    }

    /**
     * @return int
     */
    public function getMapId(): int
    {
        return $this->mapId;
    }

    /**
     * @return string
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return string
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getItemAmount()
    {
        return $this->itemAmount;
    }

    /**
     * @return string
     */
    public function getSuccessMessage()
    {
        return $this->successMessage;
    }

    /**
     * @return bool
     */
    public function getQuestComplete()
    {
        return $this->questComplete;
    }



}// end QuestAnswer class
