<?php

/**
 * An Item required to complete certain quest types. Extends Item with amount, answerBool (eg true or false),
 * answerSeq (the correct sequence in an order),, and answerSting (the correct text answer).
 */
class QuestItem extends Item {
    protected $amount;
    protected $questId;
    protected $answerBool;
    protected $answerSeq;
    protected $answerString;

    /**
     * Item constructor. Needs an item_guid.
     * @param $arg - Either the item_id and quest_id.
     * @param int $questId only used if arg is not an array
     * @throws Exception
     */
    public function __construct($arg, int $questId = null)
    {
        // This first section runs if we are already passed an array of all item info,
        // then we don't have to do any queries.
        // This section is still invoked by the constructor if we have to
        // first look up the item
        if(is_int($arg) && is_int($questId)) {
            $itemId = $arg;
            // Look up information about given item.

            // Check that item exists and throw exception if not.
            // a single item
            $sql = "SELECT qi.quest_id, qi.item_amount, qi.answer_bool, qi.answer_seq, qi.answer_string,
                i.id, i.name, i.item_category_id, ic.name AS item_category_name, i.description,
                i.item_effect_id, i.effect_parameters, i.weight, i.required_level, i.level, i.price, i.max_amount,
                i.item_icon_id, ico.name AS icon_name FROM quest_items qi
                LEFT JOIN items i ON i.id=qi.item_id
                LEFT JOIN item_categories ic ON ic.id=i.item_category_id
                LEFT JOIN item_icons ico ON ico.id=i.item_icon_id
                WHERE quest_id = ? AND qi.item_id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$questId, $itemId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new Exception("There is no quest item with the questId $questId and id $itemId");
            $arg = $stmt->fetch();

        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new Exception("Invalid call to QuestItem constructor. Must have an array of values or id and questId.");
        }

        // Create the base item
        parent::__construct($arg);

        // Add any properties specific to playerItem
        $this->questId = (int)$arg['quest_id'];
        $this->amount = (int)$arg['item_amount'];
        $this->answerBool = (bool)$arg['answer_bool'];
        $this->answerSeq = (int)$arg['answer_seq'];
        $this->answerString = $arg['answer_string'];

    }// end constructor

    /**
     * The amount of an item that a player is rewarded.
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getQuestId(): int
    {
        return $this->questId;
    }

    /**
     * @return bool
     */
    public function isAnswerBool(): bool
    {
        return $this->answerBool;
    }

    /**
     * @return bool
     */
    public function getAnswerBool(): bool
    {
        return $this->answerBool;
    }

    /**
     * @return int
     */
    public function getAnswerSeq(): int
    {
        return $this->answerSeq;
    }

    /**
     * @return mixed
     */
    public function getAnswerString()
    {
        return $this->answerString;
    }




} // end QuestItem