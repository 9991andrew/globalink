<?php

/**
 * Professions that a player may obtain. Has methods to determine if a player is eligible and
 * display the requirements for obtaining this profession.
 */
class Profession extends Dbh {
    // Array of all the bag slots with the playerItem each contains (if any)
    protected $id;
    protected $name;
    // This is  only used when this class is constructed by itself as a requiredProfession
    protected $requiredXP;
    protected $requireAllPrerequisites;
    protected $hasPrerequisite;
    protected $requiredProfessions;


    /**
     *  Main constructor
     * @param mixed $arg professions.id or array from query of profession
     * @throws InvalidProfessionException
     */
    public function __construct($arg)
    {
        if(is_int($arg)) {
            $professionId = $arg;
            // Look up profession
            $sql = "SELECT *,
                (SELECT 1 FROM profession_prerequisites pr WHERE pr.profession_id=p.id LIMIT 1) AS has_prerequisite
                FROM professions p WHERE id = ?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$professionId]);
            $count = $stmt->rowCount();
            if ($count == 0) throw new InvalidProfessionException($professionId);
            $arg = $stmt->fetch();
        } else if (!is_array($arg)) {
            // if $arg isn't an array and it isn't an int, then the constructor wasn't passed valid args
            throw new InvalidProfessionException();
        }

        $this->id = (int)$arg['id'];
        $this->name = $arg['name'];
        if (isset($arg['profession_xp_req'])) $this->requiredXP = (int)$arg['profession_xp_req'];
        $this->requireAllPrerequisites = $arg['require_all_prerequisites'] == 1;
        $this->hasPrerequisite = $arg['has_prerequisite'] == 1;

        $this->requiredProfessions = array();
        // Get list of prerequisites, if any
        if ($this->hasPrerequisite) {
            // The query looks a bit complicated so we can easily use its data to create profession objects
            // without necessarily having the constructor do another query
            $sql = "SELECT p.id, p.name, pr.profession_xp_req, p.require_all_prerequisites,
                (SELECT 1 FROM profession_prerequisites pr2 WHERE pr2.profession_id=p.id LIMIT 1) AS has_prerequisite
                FROM profession_prerequisites pr
                JOIN professions p ON pr.profession_id_req=p.id
                WHERE pr.profession_id=?;";

            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$this->id]);
            while ($row = $stmt->fetch()) {
                array_push($this->requiredProfessions, new Profession($row));
            }
        }

    }

    public function getId() {return $this->id;}
    public function getName() {return $this->name;}
    public function getRequiredXP() {return $this->requiredXP;}
    public function getRequiredProfessions() {return $this->requiredProfessions;}
    public function getRequireAllPrerequisites() {return $this->requireAllPrerequisites;}

    /**
     * Checks that a player with the specified Id is eligible to join the profession, having the required
     * prerequisite professions and profession_xp. This gets complex because a profession can have multiple
     * prerequisite professions, and the option of requiring ALL of them or ONE of them.
     * @param int $playerId
     * @return bool
     */
    public function playerIsEligible($playerId) {
        $sql = "SELECT profession_id, profession_xp FROM player_professions WHERE player_id = ?;";
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$playerId]);
        $playerProfessions = $stmt->fetchAll();

        // Default to true. If no professions are required, player is already eligible
        $eligible = true;

        // Now loop through required professions, checking that the player has this in their list of professions
        foreach ($this->requiredProfessions as $reqProf) {
            // Since now a profession is required, we set default eligibility to false until we confirm player has it
            $eligible = false;
            $playerHasRequiredProfessionAndXP = false;
            foreach ($playerProfessions as $playerProf) {
                $playerHasRequiredProfession = false;
                if ($playerProf['profession_id'] == $reqProf->getId()) {
                    // Player has a required profession
                    $playerHasRequiredProfession = true;
                    if ($playerProf['profession_xp'] >= $reqProf->getRequiredXP()) {
                        // Player also has required XP in that profession.
                        // If we only require one prerequisite, we are done
                        if (!$this->requireAllPrerequisites) return true;
                        $playerHasRequiredProfessionAndXP = true;
                        break; // We don't need to keep checking player professions, we know they have it.

                    } else if ($this->requireAllPrerequisites) {
                        // If player didn't have required XP and all are required, they don't qualify
                        return false;
                    }
                }
            }// end foreach playerProfessions
            // If we got through playerProfessions without finding this one and all are required, player isn't eligible
            if ($this->requireAllPrerequisites && !$playerHasRequiredProfessionAndXP) return false;
            // otherwise they might be eligible unless there are more professions they don't have
            else $eligible=$playerHasRequiredProfessionAndXP;
        }// end foreach requiredProfessions

        // If no professions are required, eligible started as true.
        // For each required profession, we set this to false and only set to true if they have that profession
        return $eligible;
    }

    /**
     * showProfessionRequirements outputs HTML listing the requirements to join this profession.
     * Optionally specify a id to highlight what requirements the player doesn't meet.
     *
     * Note: This code really should just return a data object that should be formatted with HTML in npc_object.php.
     * This will make localization less of a pain in the ass.
     *
     * @param null $playerId Optionally specify a player Id to highlight what requirements they meet or don't meet.
     * @return string
     */
    public function showProfessionRequirements($playerId=null): string {
        if (!is_null($playerId)) {
            $sql = "SELECT profession_id, profession_xp FROM player_professions WHERE player_id=?;";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute([$playerId]);
            $playerProfessions = $stmt->fetchAll();
        }
        // This string holds the complete report HTML
        $html = '<div>'._("There are no requirements to join this profession.").'</div>';
        if (sizeof($this->requiredProfessions)) {
            $html = '<div>'._("To join this profession, you require").' ';
            if ($this->requireAllPrerequisites && sizeof($this->requiredProfessions) > 1) $html .= _('<em>all</em> of the following professions');
            else if (sizeof($this->requiredProfessions) > 1) $html .= _('any of the following professions');
            else $html .= _('the following profession');

            $html .= ' '._('and experience:');

            $html .= '<table class="objectInfo my-2">';
        }

        // Now loop through required professions, checking that the player has this in their list of professions
        foreach ($this->requiredProfessions as $reqProf) {
            if (isset($playerProfessions)) {
                $playerHasProfession = false;
                $playerHasProfessionAndXP = false;
                foreach ($playerProfessions as $playerProf) {
                    if ($playerProf['profession_id'] == $reqProf->getId()) {
                        // Player has the required profession
                        $playerHasProfession = true;
                        if ($playerProf['profession_xp'] >= $reqProf->getRequiredXP()) {
                            // Player also has required XP in that profession.
                            // If we only require one prerequisite, we are done
                            $playerHasProfessionAndXP = true;
                            break; // We don't need to keep checking player professions, we know they have it.
                        }
                    }
                }// end foreach playerProfessions
            }// end if isset $playerProfessions
            $trClass = '';
            $thClass = '';
            $tdClass = '';
            $icon = '';
            if (isset($playerHasProfessionAndXP) && $playerHasProfessionAndXP) {
                $trClass = 'text-green-500 dark:text-green-400';
                $icon = '<i class="fas fa-check-circle"></i>';
            }
            else if (isset($playerHasProfession) && $playerHasProfession) {
                $tdClass = 'text-red-600 dark:text-red-500';
                $icon = '<i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>';
            }
            else if (isset($playerHasProfession)) $thClass = 'text-red-600 dark:text-red-500';

            $html .= '<tr class="'.$trClass.'"><td class="px-8">'.$icon.'</td><th class="text-left '.$thClass.'">'.$reqProf->getName().'</th><td class="pl-2 '.$tdClass.'">'.$reqProf->getRequiredXP().' xp</td></tr>';

        }// end foreach requiredProfessions
        if (sizeof($this->requiredProfessions)) $html .= '</table></div>';
        // If no professions are required, eligible started as true.
        // For each required profession, we set this to false and only set to true if they have that profession
        return $html;
    }

}// end class Profession
