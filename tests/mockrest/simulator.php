<?php
namespace intuitel;
require_once '../../model/Intuitel.php';
require_once ('loreRecommendation.php');
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