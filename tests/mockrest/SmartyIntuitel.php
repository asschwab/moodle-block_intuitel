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
require_once 'simulator.php';

class SmartyIntuitel extends simulator
{
var $enough_time = 30; // threshold for considering fast pace
var $too_much_time =180; // threshold for considering slow pace
var $time_window = 18000; // 5 hours time-window
var $currentKO;
var $previousKO;
var $lastDuration;
var $lastScore;
var $durations;
var $revisits;


/**
 * (non-PHPdoc)
 * @see \intuitel\simulator::process_learner_update_request()
 */
public function process_learner_update_request($loId,$learnerid,$courseLo)
{
    if ($learnerid instanceof UserId) {
            $userID = $learnerid;
        } else {
            $userID = new UserId((string) $learnerid);
        }
        $user=$this->intuitelAdaptor->getNativeUserFromUId($userID);

   // Select user interface language the same way Mooodle does:
   // First Course forced lang
   // second user preference

   $courseid=Intuitel::getIDFactory()->getIdfromLoId($courseLo->loId);
   global $DB;
  if (! $course = $DB->get_record("course", array("id" => $courseid)))
  	{
        print_error('coursemisconf');
    }
   global $USER;
   $USER=$user;
   if ($course->lang != '') {
            $USER->lang = $course->lang;
        }
        global $SESSION; $SESSION->lang=$USER->lang;

   // end language selection
   $timeFrom = time()-$this->time_window;
   $events_user = $this->intuitelAdaptor->getLearnerUpdateData(array($user->id),$courseLo,$timeFrom,null,false);
   $events=array();
   if (array_key_exists((string) $userID->id, $events_user)) {
            $events = $events_user[(string) $userID->id];
        }
        list($revisits,$durations) = IntuitelController::compute_revisits($events);
   $this->durations=$durations;
   $this->revisits=$revisits;
   $currentKOEvent=count($durations)>0?$durations[0]:null;
   $previousKOEvent=count($durations)>1?$durations[1]:null;

   $previousKO = $previousKOEvent!=null?$this->intuitelAdaptor->createLO($previousKOEvent->loId):null;
   $previous_use_data=$previousKO!=null?$this->intuitelAdaptor->getUseData($previousKO,$user->id):array(array());
   $currentKO = $currentKOEvent!=null?$this->intuitelAdaptor->createLO($currentKOEvent->loId):null;
   $current_use_data= $currentKO!=null?$this->intuitelAdaptor->getUseData($currentKO,$user->id):array(array());
   $currentKOType= $currentKOEvent!=null?Intuitel::getIDFactory()->getType($currentKOEvent->loId):null;
   $previousKOType= $previousKOEvent!=null?Intuitel::getIDFactory()->getType($previousKOEvent->loId):null;

   //
   // simulate intelligent behaviour
   //
  /**
   * Use case: Obvius and tedious tutor: Inform me what I'm doing.
   * DEBUG Use case
   */
   if (false && $currentKO) // disabled
   {

       $mid=Intuitel::getIDFactory()->getNewMessageUUID();
       $count=$revisits[(string)$currentKOEvent->loId];
       $previous='';
       if ($previousKOEvent) {
                $previous = "Antes has estado en $previousKOEvent->loId durante $previousKOEvent->duration segundos";
            }

            $this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>2</intuirr:MType>
	<intuirr:MData>Mensaje Obvio de depuración: Ahora estás en $currentKOEvent->loId donde has estado $count veces. $previous.</intuirr:MData>
</intuirr:Tug>
xml;

   }
   /**********************
    * Use case: Fast Pace User spends less than N seconds in previous LO
    */
   if ($previousKOEvent!=null
        && $previousKOEvent->duration < $this->enough_time
        && $previousKOType !='course'
        && $previousKOType !='forum'
   		&& $previousKOType !='folder'
        && (!$previous_use_data['grade'] || !$previous_use_data['grade'] > $previous_use_data['grademax']*2/3) // Do not warn if grade is already high
   )
   {
       $mid=Intuitel::getIDFactory()->getNewMessageUUID();
	$a=new \stdClass();
	$a->duration=$previousKOEvent->duration;
	$a->loId=(string)$previousKOEvent->loId;
       $advice_duration_str=get_string('advice_duration','block_intuitel',$a);
       $this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>2</intuirr:MType>
	<intuirr:MData>$advice_duration_str</intuirr:MData>
</intuirr:Tug>
xml;
       $this->selectedLORE[]=new loreRecommendation($previousKOEvent->loId,70);
   }
   /******************************
    * Use case: out-of-sequence
    */
   $previousSiblingCount = $currentKO&&$currentKO->hasPrecedingSib&&array_key_exists($currentKO->hasPrecedingSib->id,$revisits)?$revisits[$currentKO->hasPrecedingSib->id]:0;


   if ( $currentKO && $currentKO->hasPrecedingSib     // there is an usable sequence
        &&
        $previousSiblingCount==0 // not visited
        &&
        !($current_use_data['grade'] > $current_use_data['grademax']*2/3)// currentKO is not passed
        )
        {
            $mid=Intuitel::getIDFactory()->getNewMessageUUID();
            $a=new \stdClass();
            $a->previousLoId=(string)$currentKO->hasPrecedingSib;
            $a->currentLoId = (string)$currentKO->loId;
            $advice_sequence_str=get_string('advice_outofsequence','block_intuitel',$a);
            $this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>2</intuirr:MType>
	<intuirr:MData>$advice_sequence_str</intuirr:MData>
</intuirr:Tug>
xml;
            // recommend
            $this->selectedLORE[]=new loreRecommendation($currentKO->hasPrecedingSib, 100);
            $this->selectedLORE[]=new loreRecommendation($currentKO->loId, 100);
        }
   /*******************************
    * Use case: excessive revisits
    */
//    print_object($this->revisits[$currentKOEvent->loId->id]);die;

   if ($currentKOEvent!=null
        && $this->revisits[$currentKOEvent->loId->id] > 2
        && $currentKOType !='course'
        && $currentKOType !='forum'
    	&& $currentKOType !='folder')
   {
       $count = $revisits[$currentKOEvent->loId->id];

       $mid=Intuitel::getIDFactory()->getNewMessageUUID();

       $params_str = new \stdClass();
       $params_str->count=$count;
       $params_str->loId=(string)$currentKOEvent->loId;

  $advice_revisits_str=get_string('advice_revisits','block_intuitel',$params_str);

  $this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>2</intuirr:MType>
	<intuirr:MData>$advice_revisits_str</intuirr:MData>
</intuirr:Tug>
xml;
   }

   /*********
    * Test case. Graded activity is abandoned with low grade
    ***********/
   if ($previousKOEvent!=null
        && $previous_use_data['grade'] < $previous_use_data['grademax']*2/3)
   {
      $grade= number_format($previous_use_data['grade']);
      $grademax= number_format($previous_use_data['grademax']);
      $mid=Intuitel::getIDFactory()->getNewMessageUUID();

      $params_str = new \stdClass();
      $params_str->loId=(string)$previousKOEvent->loId;
      $params_str->grade=$grade;
      $params_str->grademax=$grademax;

      $advice_grade_str=get_string('advice_grade','block_intuitel',$params_str);
      $this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>2</intuirr:MType>
	<intuirr:MData>$advice_grade_str</intuirr:MData>
</intuirr:Tug>
xml;
      // recommends previous LO
      if (isset ($previousKO->hasPrecedingSib))
        $this->selectedLORE[]=new loreRecommendation($previousKO->hasPrecedingSib, 100);
      $this->selectedLORE[]=new loreRecommendation($previousKOEvent->loId, 80);

   }

   /*********
    * Test case. Graded activity is abandoned with high grade. The student is congratulated for good result
   ***********/
   if ($previousKOEvent!=null
   && $previous_use_data['grade'] > $previous_use_data['grademax']*2/3)
   {
   	$grade= number_format($previous_use_data['grade']);
   	$grademax= number_format($previous_use_data['grademax']);
   	$mid=Intuitel::getIDFactory()->getNewMessageUUID();
   	$params_str = new \stdClass();
   	$params_str->loId=(string)$previousKOEvent->loId;
   	$params_str->grade=$grade;
   	$params_str->grademax=$grademax;
   	$congratulate_grade_str=get_string('congratulate_grade','block_intuitel',$params_str);
   	$this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>1</intuirr:MType>
	<intuirr:MData>$congratulate_grade_str</intuirr:MData>
</intuirr:Tug>
xml;
   	// recommends previous LO
   	if (isset ($previousKO->hasPrecedingSib))
   		$this->selectedLORE[]=new loreRecommendation($previousKO->hasPrecedingSib, 100);
   	$this->selectedLORE[]=new loreRecommendation($previousKOEvent->loId, 80);

   }
   /*********
    * Test case. Graded activity is visited with high grade. The student is remembered about
   ***********/
   if ($currentKOEvent!=null
   && $current_use_data['grade'] > $current_use_data['grademax']*2/3)
   {
      	$grade= number_format($current_use_data['grade']);
      	$grademax= number_format($current_use_data['grademax']);
      	$mid=Intuitel::getIDFactory()->getNewMessageUUID();
      	$params_str = new \stdClass();
      	$params_str->loId=(string)$currentKOEvent->loId;
      	$params_str->grade=$grade;
      	$params_str->grademax=$grademax;
      	$remember_graded_str=get_string('remember_already_graded','block_intuitel',$params_str);
      	$this->tugFragment.= <<<xml
<intuirr:Tug uId="jmb0001" mId="$mid">
	<intuirr:MType>1</intuirr:MType>
	<intuirr:MData>$remember_graded_str</intuirr:MData>
</intuirr:Tug>
xml;

   }
   /**
    * First LO in the course
    */
$parent = $currentKO->hasParent!=null?$this->intuitelAdaptor->createLO($currentKO->hasParent):null;

if ($currentKO->hasPrecedingSib==null && $parent!=null && $parent->hasPrecedingSib==null)
   {
//        $this->tugFragment.= <<<xml
// <intuirr:Tug uId="intuitelstudent1" mId="db4d7ada-58fb-4939-ba0d-41c9ac585efd" rId="dcbcf826-a5fe-4fe8-bcc2-609c299afe04">
//     <intuirr:MType>4</intuirr:MType>
//     <intuirr:MData xsi:type="xs:string" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">&lt;![CDATA[&lt;h4&gt;You just started the available material. Very good!&lt;/h4&gt;\nPlease, select a Learning Pathway: &lt;select name="invalidateLpSelection[]" &gt;&lt;option name="LpSelection-1" value="1"&gt;Classically structured learning path sequenced by matters.&lt;/option&gt;&lt;option name="LpSelection-N" value="2"&gt;Hierarchically structured material organized by levels of abstraction.&lt;/option&gt;&lt;/select&gt;]]&gt;</intuirr:MData>
//     </intuirr:Tug>
// xml;

$this->tugFragment.= <<<xml
<intuirr:Tug uId="intuitelstudent1" mId="db4d7ada-58fb-4939-ba0d-41c9ac585efd" rId="dcbcf826-a5fe-4fe8-bcc2-609c299afe04">
    <intuirr:MType>4</intuirr:MType>
    <intuirr:MData xsi:type="xs:string" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    &lt;![CDATA[
    &lt;h4&gt;You just started the available material. Very good!&lt;/h4&gt;
    Please, select a Learning Pathway:&lt;br/&gt;
    &lt;input type=&quot;radio&quot; name=&quot;invalidateLpSelection[]&quot; value=&quot;1&quot;&gt;Classically structured learning path sequenced by matters.&lt;/input&gt;&lt;br/&gt;
	&lt;input type=&quot;radio&quot; name=&quot;invalidateLpSelection[]&quot; value=&quot;2&quot;&gt;Hierarchically structured material organized by levels of abstraction.&lt;/input&gt;&lt;br/&gt;
	]]&gt;</intuirr:MData>
    </intuirr:Tug>
xml;
   }
/**
 * Last LO in the course
 */
if ($currentKO->hasFollowingSib==null && $parent!=null && $parent->hasFollowingSib==null)
{
$this->tugFragment.= <<<xml
<intuirr:Tug uId="intuitelstudent1" mId="db4d7ada-58fb-4939-ba0d-41c9ac585efd" rId="dcbcf826-a5fe-4fe8-bcc2-609c299afe04">
    <intuirr:MType>4</intuirr:MType>
    <intuirr:MData xsi:type="xs:string" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">&lt;![CDATA[&lt;h4&gt;You just completed all your assigned material. Very good!&lt;/h4&gt;\nDo you want to restart it following another Learning Pathway? &lt;select name="invalidateLpSelection[]" &gt;&lt;option name="invalidateLpSelection-Y" value="Y"&gt;yes&lt;/option&gt;&lt;option name="invalidateLpSelection-N" value="N"&gt;no&lt;/option&gt;&lt;/select&gt;]]&gt;</intuirr:MData>
    </intuirr:Tug>
xml;
}
 /*
   $this->tugFragment.= <<<xml
<Tug uId="jmb0001" mId="12345678-1234-abcd-ef12-123456789014">
	<MType>1</MType>
	<MData>Estás visitando $currentKOEvent->loId y vienes de $previousKOEvent->loId donde has estado $previousKOEvent->duration segundos.</MData>
</Tug>
xml;

   */
   // if currentKO is the course use previousKO
   if (!isset($currentKO->hasParent)) // course
   {
       $currentKO = $previousKO;
   }
   if (isset ($currentKO->hasFollowingSib))
   {
       $following= $this->intuitelAdaptor->createLO($currentKOEvent->loId);
       $followingType = Intuitel::getIDFactory()->getType($following->loId);
       if ($followingType=='section')
           $this->selectedLORE[]=new loreRecommendation($following->hasFollowingSib, 70);
       else
           $this->selectedLORE[]=new loreRecommendation($currentKO->hasFollowingSib, 70);
   }
   else
   if (isset ($currentKO->hasParent))// suppose section
   {
       $sectionLo= $currentKO->hasParent?$this->intuitelAdaptor->createLO($currentKO->hasParent):null; //section
       $nextsectionLo = $sectionLo!=null && $sectionLo->hasFollowingSib?$this->intuitelAdaptor->createLO($sectionLo->hasFollowingSib):null;// next section
       if ($nextsectionLo && isset($nextsectionLo->hasChild[0]))
           $this->selectedLORE[]=new loreRecommendation($nextsectionLo->hasChild[0], 70);
   }


 // simulate a sequencing
    $lastLoreRec = count($this->selectedLORE)>0?$this->selectedLORE[count($this->selectedLORE)-1]:null;
    $lastLoreId= $lastLoreRec?$lastLoreRec->loId:null;
    for($i=0;$i<4;$i++)
    {
        if (!$lastLoreId)
            break;
       try{
           $loreLO = $this->intuitelAdaptor->createLO($lastLoreId);

           if (isset($loreLO->hasFollowingSib))
            {
                $lastLoreId=$loreLO->hasFollowingSib;
                $this->selectedLORE[]=new loreRecommendation( $lastLoreId, $lastLoreRec->score/($i+2));
            }
        }catch(UnknownLOTypeException $e)
        {
            break;
        }
    }

}



}
