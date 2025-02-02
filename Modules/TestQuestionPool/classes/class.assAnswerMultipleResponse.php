<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

include_once "./Modules/Test/classes/inc.AssessmentConstants.php";

/**
* Class for true/false or yes/no answers
*
* ASS_AnswerMultipleResponse is a class for answers with a binary state indicator (checked/unchecked, set/unset)
*
* @author		Helmut Schottmüller <helmut.schottmueller@mac.com>
* @version	$Id$
* @ingroup ModulesTestQuestionPool
* @see ASS_AnswerSimple
*/
class ASS_AnswerMultipleResponse extends ASS_AnswerSimple
{
    /**
    * The points given to the answer when the answer is not checked
    *
    * The points given to the answer when the answer is not checked
    *
    * @var float|int|string|null
    */
    public $points_unchecked;

    /**
    * ASS_AnswerMultipleResponse constructor
    *
    * The constructor takes possible arguments an creates an instance of the ASS_AnswerMultipleResponse object.
    *
    * @param string $answertext A string defining the answer text
    * @param double $points The number of points given for the selected answer
    * @param double $points_unchecked The points when the answer is not checked
    * @param integer $order A nonnegative value representing a possible display or sort order
    * @access public
    */
    public function __construct(string $answertext = "", float $points = 0.0, int $order = 0, int $id = -1, int $state = 0)
    {
        parent::__construct($answertext, $points, $order, $id, $state);
    }


    /**
    * Returns the points for an unchecked answer
    *
    * Returns the points for an unchecked answer

    * @return double The points for an unchecked answer
    * @access public
    * @see $points_unchecked
    */
    public function getPointsUnchecked(): float
    {
        return $this->points_unchecked;
    }

    /**
    * Sets the points for an unchecked answer
    *
    * Sets the points for an unchecked answer
    *
    * @param double $points_unchecked The points for an unchecked answer
    * @access public
    * @see $state
    */
    public function setPointsUnchecked($points_unchecked = 0.0): void
    {
        $new_points = str_replace(",", ".", $points_unchecked);

        if ($this->checkPoints($new_points)) {
            $this->points_unchecked = $new_points;
        } else {
            $this->points_unchecked = 0.0;
        }
    }

    public function setPointsChecked($points_checked): void
    {
        $this->setPoints($points_checked);
    }

    public function getPointsChecked(): float
    {
        return $this->getPoints();
    }
}
