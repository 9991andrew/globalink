<?php

/**
 * Extends QuestAnswer adding any variables associated with this quest.
 */
class PlayerQuestAnswer extends QuestAnswer
{
    protected $playerId;
    protected $variables; // Player's quest variables values

    /**
     * Constructor. Needs a an array of values (from pre-existing query)
     * @param any $arg
     * @param int $playerId
     * @param array $variables player's variable values (for quests using them).
     * @throws Exception
     */
    public function __construct($arg, $playerId, $variables=null)
    {
        // Create the base QuestAnswer
        parent::__construct($arg);

        // Also add the player ID
        $this->playerId = $playerId;

        if (!isset($variables)) {
            $this->variables = array();
        } else {
            $this->variables = $variables;
        }

        // Override the answerString and question with the player's variables
        $this->answerString = Helpers::injectVariables($this->answerString, $this->variables);
        $this->question = Helpers::injectVariables($this->question, $this->variables);

    }

    /**
     * Override getAnswerString() to calculate the correct answer
     *
     * Here we handle some basic math expressions in a safe way.
     * Supported operators:
     * +, -, *, /, ^, pow(), sqrt(), % (modulo), brackets ()]
     * Should I support factorial (!)?
     * One question implies factorial factorial, but I think those are exclamation marks
     */
    public function getAnswerString()
    {
        // This should only parse as an equation if it is of an equation type
        return $this->answerString;
    }


}