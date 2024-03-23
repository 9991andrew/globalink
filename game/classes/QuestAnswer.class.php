<?php
/**
 * Specifies the answers or options a quest has associated with it, and all the metadata it uses,
 * like questions, and whether an answer is true or false.
 */

class QuestAnswer extends Dbh {
    protected $id;
    // Name user logs in with
    protected $questId;
    protected $name;
    protected $question;
    protected $answerString;
    // If the question uses a true/false or multiple choice, the correct value will be boolean
    protected $correctBool;

    /**
     * Constructor. Needs a an array of values (from pre-existing query)
     * @param any $arg
     * @throws Exception
     */
    public function __construct($arg)
    {
        if (!is_array($arg)) {
            // if $arg isn't an array the constructor wasn't passed valid args
            throw new Exception('Invalid argument passed to QuestAnswer constructor.');
        }
        $this->id = (int)$arg['id'];
        $this->questId = (int)$arg['quest_id'];
        $this->name = $arg['name'];
        $this->question = $arg['question'];
        $this->answerString = $arg['answer_string'];
        $this->correctBool = $arg['correct_bool'];
    }

    public function getId() {return $this->id;}
    public function getQuestId() {return $this->questId;}
    public function getName() {return $this->name;}
    public function getQuestion() {return $this->question;}
    public function getAnswerString() {return $this->answerString;}
    public function getCorrectBool() {return $this->correctBool;}

}// end QuestAnswer class
