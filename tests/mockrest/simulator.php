<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Emulation of an INTUITEL Engine (for debugging purpouses)
 *
 * @package    block_intuitel
 * @author Juan Pablo de Castro, Elena Verdú.
 * @copyright  2015 Intuitel Consortium
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace intuitel;
require_once '../../model/Intuitel.php';
require_once('loreRecommendation.php');
abstract class simulator
{
    /**
     *
     * @var IntuitelAdaptor
     */
var $intuitelAdaptor;
var $course;
/**
 *
 * @var array(intuitelLO)
 */
var $selectedLORE=array();
var $tugFragment;

function __construct(IntuitelAdaptor $adaptor,$course)
{
    $this->intuitelAdaptor=$adaptor;
    $this->course=$course;
}
/**
 * @return array<loreRecommendation>
 */
public function get_LO_recommendation()
{

    // sort by score
    $scores=array();
    foreach ($this->selectedLORE as $rec) {
        $scores[]  = $rec->score;
    }
    array_multisort($scores,SORT_DESC, $this->selectedLORE);

    // filter out duplicated recommendations
    $lore_ids = array();
    $lores=array();

    foreach ($this->selectedLORE as $lore)
    {
        if (!array_key_exists((string)$lore->loId,$lore_ids))
        {
            $lores[]=$lore;
            $lore_ids[(string)$lore->loId]=$lore;
        }
    }

  return $lores;
}

public function get_tug_fragment($index=null)
{
return $this->tugFragment;
}
/**
 *
 * @param string $loId
 * @param string $learnerid
 * @param CourseLo $courseLo
 */
public abstract function process_learner_update_request($loId,$learnerid,$courseLo);
}